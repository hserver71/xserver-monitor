<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Server;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ServerApiController extends Controller
{
    /**
     * Get servers for a specific client
     */
    public function getClientServers(Client $client)
    {
        try {
            $servers = $client->servers()->get();
            
            return response()->json([
                'success' => true,
                'servers' => $servers,
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'ip' => $client->ip,
                    'domain' => $client->domain
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching client servers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching servers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function fetchServersFromClientDomain(Request $request)
    {   

        try {
            $request->validate([
                'client_id' => 'required|exists:clients,id'
            ]);

            $client = Client::findOrFail($request->client_id);
$useTestMode = $request->input('test_mode', false);
            if ($useTestMode) {
                return $this->processTestData($client);
            }
            $clientApiUrl = $this->buildClientApiUrl($client->ip);
            $this->testConnection($clientApiUrl);
            $currentServers = Server::where('client_id', $client->id)->get();
            $currentServerCount = $currentServers->count();
            $currentServerIps = $currentServers->pluck('ip')->toArray();
            $startTime = microtime(true);
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Laravel-Server-Monitor/1.0',
                    'Accept' => 'application/json, text/plain, */*',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache'
                ])
                ->withoutVerifying() // Skip SSL verification for development
                ->get($clientApiUrl);
            $requestTime = microtime(true) - $startTime;


            if (!$response->successful()) {
                $errorDetails = [
                    'status' => $response->status(),
                    'status_text' => $response->reasonPhrase(),
                    'headers' => $response->headers(),
                    'body_preview' => substr($response->body(), 0, 200),
                    'url' => $clientApiUrl
                ];
                switch ($response->status()) {
                    case 404:
                        throw new \Exception('Client API endpoint not found (404). Please verify the URL: ' . $clientApiUrl);
                    case 403:
                        throw new \Exception('Access forbidden (403). This might be a CORS issue or server configuration problem.');
                    case 500:
                        throw new \Exception('Client server error (500). The client\'s server is experiencing issues.');
                    case 0:
                        throw new \Exception('Connection failed. This could be due to CORS, network issues, or the server being unreachable.');
                    default:
                        throw new \Exception('Client API request failed with status ' . $response->status());
                }
            }

            $apiData = $response->json();
            DB::beginTransaction();
            
            try {
                // Process API data and track new/updated servers
                $newServerIps = [];
                $updatedCount = 0;
                $createdCount = 0;
                $errors = [];
                $processedServers = [];

                foreach ($apiData as $index => $serverData) {
                    $itemDebug = [
                        'index' => $index,
                        'server_data' => $serverData,
                        'data_keys' => array_keys($serverData)
                    ];

                    try {
                        // Validate server data structure
                        if (!isset($serverData['server_name']) || !isset($serverData['server_ip'])) {
                            $errorMsg = 'Invalid server data structure: ' . json_encode($serverData);
                            $errors[] = $errorMsg;
                            Log::warning('Server data validation failed', $itemDebug + ['error' => $errorMsg]);
                            continue;
                        }

                        // Validate IP format
                        if (!filter_var($serverData['server_ip'], FILTER_VALIDATE_IP)) {
                            $errorMsg = 'Invalid IP format: ' . $serverData['server_ip'];
                            $errors[] = $errorMsg;
                            Log::warning('IP validation failed', $itemDebug + ['error' => $errorMsg]);
                            continue;
                        }

                        $newServerIps[] = $serverData['server_ip'];

                        // Check if server already exists for this client
                        $existingServer = Server::where('client_id', $client->id)
                            ->where('ip', $serverData['server_ip'])
                            ->first();

                        if ($existingServer) {
                            // Update existing server
                            $oldData = $existingServer->toArray();
                            $existingServer->update([
                                'name' => $serverData['server_name'],
                                'domain' => $serverData['domain_name'] ?? $existingServer->domain,
                                'updated_at' => now()
                            ]);
                            $existingServer->refresh();
                            
                            $updatedCount++;
                            $processedServers[] = [
                                'action' => 'updated',
                                'server_id' => $existingServer->id,
                                'old_data' => $oldData,
                                'new_data' => $existingServer->toArray()
                            ];

                        } else {
                            // Create new server
                            $newServer = Server::create([
                                'client_id' => $client->id,
                                'name' => $serverData['server_name'],
                                'ip' => $serverData['server_ip'],
                                'domain' => $serverData['domain_name'] ?? null
                            ]);
                            
                            $createdCount++;
                            $processedServers[] = [
                                'action' => 'created',
                                'server_id' => $newServer->id,
                                'new_data' => $newServer->toArray()
                            ];

                        }
                    } catch (\Exception $e) {
                        $errorMsg = 'Error processing server ' . ($serverData['server_name'] ?? 'Unknown') . ': ' . $e->getMessage();
                        $errors[] = $errorMsg;
                        Log::error('Server processing error', $itemDebug + ['error' => $errorMsg, 'trace' => $e->getTraceAsString()]);
                    }
                }
                $removedCount = 0;
                $serversToRemove = array_diff($currentServerIps, $newServerIps);
                
                if (!empty($serversToRemove)) {
                    $serversToDelete = Server::where('client_id', $client->id)
                        ->whereIn('ip', $serversToRemove)
                        ->get();

                    Log::info('Servers to be removed', [
                        'ips_to_remove' => $serversToRemove,
                        'servers_detail' => $serversToDelete->toArray()
                    ]);
                    $removedCount = Server::where('client_id', $client->id)
                        ->whereIn('ip', $serversToRemove)
                        ->delete();
                } else {
                    Log::info('No servers to remove - all current servers exist in API response');
                }

                // Remove duplicates (keep only the latest version)
                $duplicateCount = $this->removeDuplicateServers($client->id);
                
                Log::info('Duplicate removal completed', [
                    'duplicates_removed' => $duplicateCount
                ]);

                // Commit transaction
                DB::commit();
                Log::info('Database transaction committed successfully');

                // Get final server count
                $finalServerCount = Server::where('client_id', $client->id)->count();
                $finalServerIps = Server::where('client_id', $client->id)->pluck('ip')->toArray();

                // Log the operation summary
                $operationSummary = [
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'client_domain' => $client->domain,
                    'api_url' => $clientApiUrl,
                    'initial_server_count' => $currentServerCount,
                    'final_server_count' => $finalServerCount,
                    'created' => $createdCount,
                    'updated' => $updatedCount,
                    'removed' => $removedCount,
                    'duplicates_removed' => $duplicateCount,
                    'errors' => $errors,
                    'processed_servers' => $processedServers,
                    'initial_ips' => $currentServerIps,
                    'final_ips' => $finalServerIps,
                    'memory_usage_after' => memory_get_usage(true),
                    'peak_memory' => memory_get_peak_usage(true)
                ];

                Log::info('=== SUCCESS: Fetch Servers from Client Domain ===', $operationSummary);

                return response()->json([
                    'success' => true,
                    'message' => "Servers fetched successfully from {$client->domain}. Created: {$createdCount}, Updated: {$updatedCount}, Removed: {$removedCount}, Duplicates removed: {$duplicateCount}",
                    'data' => [
                        'client_domain' => $client->domain,
                        'api_url' => $clientApiUrl,
                        'created' => $createdCount,
                        'updated' => $updatedCount,
                        'removed' => $removedCount,
                        'duplicates_removed' => $duplicateCount,
                        'initial_count' => $currentServerCount,
                        'final_count' => $finalServerCount,
                        'errors' => $errors,
                        'debug_info' => [
                            'request_time_seconds' => round($requestTime, 3),
                            'memory_usage' => memory_get_usage(true),
                            'peak_memory' => memory_get_peak_usage(true)
                        ]
                    ]
                ]);

            } catch (\Exception $e) {
                // Rollback transaction on error
                DB::rollBack();
                Log::error('Database transaction rolled back due to error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            $errorInfo = [
                'client_id' => $request->client_id ?? 'unknown',
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true)
            ];

            Log::error('=== ERROR: Fetch Servers from Client Domain ===', $errorInfo);
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching servers from client domain: ' . $e->getMessage(),
                'debug_info' => [
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Build the client's API URL
     */
    private function buildClientApiUrl($domain)
    {
        Log::info('Building client API URL', [
            'input_domain' => $domain,
            'has_protocol' => preg_match('/^https?:\/\//', $domain),
            'protocol_match' => preg_match('/^https?:\/\//', $domain) ? 'yes' : 'no'
        ]);

        $originalDomain = $domain;
        
        // Ensure domain has protocol
        if (!preg_match('/^https?:\/\//', $domain)) {
            $domain = 'http://' . $domain;
            Log::info('Added HTTP protocol', [
                'original' => $originalDomain,
                'with_protocol' => $domain
            ]);
        } else {
            Log::info('Domain already has protocol', ['domain' => $domain]);
        }
        
        // Remove trailing slash if present
        $domainBeforeTrim = $domain;
        $domain = rtrim($domain, '/');
        
        if ($domainBeforeTrim !== $domain) {
            Log::info('Removed trailing slash', [
                'before_trim' => $domainBeforeTrim,
                'after_trim' => $domain
            ]);
        }
        
        $finalUrl = $domain . '/block_actions.php?action=server';
        
        Log::info('Final API URL built', [
            'input_domain' => $originalDomain,
            'final_url' => $finalUrl,
            'url_components' => parse_url($finalUrl),
            'full_url_breakdown' => [
                'protocol' => parse_url($finalUrl, PHP_URL_SCHEME),
                'host' => parse_url($finalUrl, PHP_URL_HOST),
                'port' => parse_url($finalUrl, PHP_URL_PORT),
                'path' => parse_url($finalUrl, PHP_URL_PATH),
                'query' => parse_url($finalUrl, PHP_URL_QUERY)
            ]
        ]);
        
        return $finalUrl;
    }

    /**
     * Remove duplicate servers for a client
     * Keeps the most recently updated server for each IP
     */
    private function removeDuplicateServers($clientId)
    {
        Log::info('Starting duplicate server removal', ['client_id' => $clientId]);

        // Find all servers for this client
        $allServers = Server::where('client_id', $clientId)->get();
        Log::info('All servers for client', [
            'client_id' => $clientId,
            'total_servers' => $allServers->count(),
            'servers_detail' => $allServers->toArray()
        ]);

        // Find duplicates
        $duplicates = DB::table('servers as s1')
            ->join('servers as s2', function($join) {
                $join->on('s1.ip', '=', 's2.ip')
                     ->on('s1.client_id', '=', 's2.client_id')
                     ->on('s1.id', '<', 's2.id');
            })
            ->where('s1.client_id', $clientId)
            ->select('s1.id', 's1.ip', 's1.name', 's1.updated_at')
            ->get();

        Log::info('Duplicate servers found', [
            'client_id' => $clientId,
            'duplicate_count' => $duplicates->count(),
            'duplicates_detail' => $duplicates->toArray()
        ]);

        if ($duplicates->count() > 0) {
            $duplicateIds = $duplicates->pluck('id')->toArray();
            
            // Get details of servers to be deleted
            $serversToDelete = Server::whereIn('id', $duplicateIds)->get();
            
            Log::info('Servers to be deleted (duplicates)', [
                'client_id' => $clientId,
                'duplicate_ids' => $duplicateIds,
                'servers_to_delete' => $serversToDelete->toArray()
            ]);

            // Delete duplicates
            $deletedCount = Server::whereIn('id', $duplicateIds)->delete();
            
            Log::info('Duplicate servers deleted', [
                'client_id' => $clientId,
                'deleted_count' => $deletedCount,
                'deleted_ids' => $duplicateIds
            ]);

            return $deletedCount;
        }

        Log::info('No duplicate servers found', ['client_id' => $clientId]);
        return 0;
    }

    /**
     * Process test data for development/testing purposes
     */
    private function processTestData($client)
    {
        Log::info('Processing test data for client', [
            'client_id' => $client->id,
            'client_name' => $client->name
        ]);

        // Mock server data
        $mockServers = [
            [
                'name' => 'Test Server 1',
                'ip' => '192.168.1.100',
                'domain' => 'test1.' . $client->domain
            ],
            [
                'name' => 'Test Server 2',
                'ip' => '192.168.1.101',
                'domain' => 'test2.' . $client->domain
            ],
            [
                'name' => 'Test Server 3',
                'ip' => '192.168.1.102',
                'domain' => 'test3.' . $client->domain
            ]
        ];

        Log::info('Mock server data created', [
            'server_count' => count($mockServers),
            'servers' => $mockServers
        ]);

        // Start database transaction
        Log::info('Starting database transaction for test data');
        DB::beginTransaction();

        try {
            $newServerIps = [];
            $createdCount = 0;
            $updatedCount = 0;
            $processedServers = [];

            foreach ($mockServers as $serverData) {
                $newServerIps[] = $serverData['ip'];

                // Check if server already exists
                $existingServer = Server::where('client_id', $client->id)
                    ->where('ip', $serverData['ip'])
                    ->first();

                if ($existingServer) {
                    // Update existing server
                    $existingServer->update([
                        'name' => $serverData['name'],
                        'domain' => $serverData['domain'],
                        'updated_at' => now()
                    ]);
                    $updatedCount++;
                    $processedServers[] = [
                        'action' => 'updated',
                        'server_id' => $existingServer->id,
                        'server_data' => $serverData
                    ];
                } else {
                    // Create new server
                    $newServer = Server::create([
                        'name' => $serverData['name'],
                        'ip' => $serverData['ip'],
                        'domain' => $serverData['domain'],
                        'client_id' => $client->id
                    ]);
                    $createdCount++;
                    $processedServers[] = [
                        'action' => 'created',
                        'server_id' => $newServer->id,
                        'server_data' => $serverData
                    ];
                }
            }

            // Remove servers that are no longer in the mock data
            $serversToRemove = Server::where('client_id', $client->id)
                ->whereNotIn('ip', $newServerIps)
                ->get();

            $removedCount = 0;
            if ($serversToRemove->count() > 0) {
                $removedIds = $serversToRemove->pluck('id')->toArray();
                Server::whereIn('id', $removedIds)->delete();
                $removedCount = count($removedIds);
                
                Log::info('Test servers removed', [
                    'removed_count' => $removedCount,
                    'removed_ids' => $removedIds
                ]);
            }

            // Remove duplicates
            $duplicatesRemoved = $this->removeDuplicateServers($client->id);

            DB::commit();

            Log::info('Test data processing completed successfully', [
                'client_id' => $client->id,
                'created_count' => $createdCount,
                'updated_count' => $updatedCount,
                'removed_count' => $removedCount,
                'duplicates_removed' => $duplicatesRemoved,
                'processed_servers' => $processedServers
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test servers processed successfully',
                'data' => [
                    'created' => $createdCount,
                    'updated' => $updatedCount,
                    'removed' => $removedCount,
                    'duplicates_removed' => $duplicatesRemoved,
                    'total_processed' => count($processedServers)
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing test data', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing test data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getServers()
    {
        $servers = Server::all();
        $result = [];
        foreach ($servers as $server) {
            $result[] = [
                'domain' => $server->domain,
                'ip' => $server->ip,
            ];
        }
        return response()->json($result);
    }

    /**
     * Test connection to client's API endpoint
     */
    private function testConnection($url)
    {
        Log::info('Testing connection to client API', ['url' => $url]);
        
        try {
            // Try a simple HEAD request first (lighter than GET)
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Laravel-Server-Monitor/1.0',
                    'Accept' => '*/*'
                ])
                ->withoutVerifying()
                ->head($url);
            
            Log::info('Connection test result', [
                'url' => $url,
                'status' => $response->status(),
                'headers' => $response->headers(),
                'connection_successful' => $response->successful()
            ]);
            
            if ($response->successful()) {
                Log::info('Connection test passed', ['url' => $url]);
                return true;
            } else {
                Log::warning('Connection test failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'reason' => $response->reasonPhrase()
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('Connection test exception', [
                'url' => $url,
                'error' => $e->getMessage(),
                'error_type' => get_class($e)
            ]);
            
            // Don't throw here, just log the issue
            return false;
        }
    }
} 