<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Vps;
use App\Models\Line;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Server;

class LinesController extends Controller
{
    /**
     * Display the lines page with clients, lines, and VPS sections
     */
    public function index()
    {
        $clients = Client::all();
        $vps = Vps::all();
        return view('lines.index', compact('clients', 'vps'));
    }

    /**
     * Check line status and assign domains to VPS instances
     * API endpoint: /check-line-status?line=username
     */
    public function checkLineStatus(Request $request)
    {
        try {
            // Get the line username from query parameter
            $lineUsername = $request->query('line');
            if (!$lineUsername) {
                return response()->json(['success' => false, 'message' => 'Line parameter is required'], 400);
            }

            // Get client's real IP address
            $clientIp = $request->ip();
            
            // Alternative methods to get client IP
            $realIp = $request->header('X-Real-IP');
            $forwardedIp = $request->header('X-Forwarded-For');
            $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            // Parse origin address to get client domain
            $origin = $request->header('Origin') ?? $request->header('Referer');

            // Extract domain from origin (remove protocol and port)
            $parsedUrl = parse_url($origin);
            $clientIp = $request->header('X-Real-IP');
            
            if (!$clientIp) {
                return response()->json(['success' => false, 'message' => 'Could not parse client domain from origin'], 400);
            }

            // Find client by domain
            $client = Client::where('ip', $clientIp)->first();
            
            if (!$client) {
                return response()->json(['success' => false, 'message' => 'Client not found for domain: ' . $clientIp], 404);
            }

            // Find line by username and client
            $line = Line::where('username', $lineUsername)
                       ->where('client_id', $client->id)
                       ->first();
            
            if (!$line) {
                return response()->json(['success' => false, 'message' => 'Line not found for username: ' . $lineUsername], 404);
            }

            // Get VPS instances for this client
            // $vpsInstances = Vps::where('client_id', $client->id)->get();
            
            // if ($vpsInstances->isEmpty()) {
            //     return response()->json(['success' => false, 'message' => 'No VPS instances found for this client'], 404);
            // }

            // Get servers for this client
            $servers = Server::where('client_id', $client->id)->get();
            $domainConfigs = [];
            $cleanDomain = '';
            $domainConfigs = array();; // single object

            foreach ($servers as $server) {
                Log::info('Server: ' . $server->domain);

                if ($server->domain) {
                    $domains = array_map('trim', explode(',', $server->domain));

                    foreach ($domains as $domain) {
                        if ($domain && str_starts_with($domain, '*.')) {
                            $cleanDomain = ltrim($domain, '*.');
                            $domainConfigs[] = [
                                'main_domain' => $lineUsername . '.' . $cleanDomain,
                                'proxy' => $cleanDomain,
                            ];
                            break 2; // stop both loops after first * domain
                        }
                    }
                }
            }

            Log::info('Domain Configs: ' . json_encode($domainConfigs));

            if (empty($domainConfigs)) {
                return response()->json(['success' => false, 'message' => 'No valid domains found for configuration'], 400);
            };

            // Configure nginx on each VPS
            $configuredVpsCount = 0;
            $errors = [];
            $targetVPS = Vps::where('linename', null)
                ->orWhere('linename', '')
                ->first();
            if (!$targetVPS) {
                return response()->json(['success' => false, 'message' => 'No VPS found for configuration'], 400);
            };
            try {
                if (!$targetVPS->ip || !$targetVPS->username || !$targetVPS->password) {
                    $errors[] = "VPS {$targetVPS->id} missing credentials (IP, username, or password)";
                }
                $sshService = new \App\Services\SSHService($targetVPS->ip, $targetVPS->username, $targetVPS->password);
                $sshService->connect();
                $nginxStatus = $sshService->checkNginxStatus();
                if (!$nginxStatus['installed'] || !$nginxStatus['running']) {
                    Log::info('Installing nginx on VPS', ['vps_id' => $targetVPS->id, 'ip' => $targetVPS->ip]);
                    $sshService->installNginxProxy();
                }
                Log::info('Configuring nginx with domain configs', [
                    'vps_id' => $targetVPS->id,
                    'domain_configs' => $domainConfigs
                ]);
                $sshService->configureNginxWithDomainConfigs($domainConfigs);
                $sshService->disconnect();
                $targetVPS->update([
                    'linename' => $lineUsername,
                    'serverdomain' => json_encode($domainConfigs),
                    'domains' => json_encode($domainConfigs),
                    'assigned_at' => now()
                ]);
                
                $configuredVpsCount++;
                Log::info('Successfully configured VPS ' . $targetVPS->id);

            } catch (\Exception $sshException) {
                Log::error('SSH/Nginx configuration failed for VPS ' . $targetVPS->id, [
                    'vps_id' => $targetVPS->id,
                    'error' => $sshException->getMessage()
                ]);
                $errors[] = "VPS {$targetVPS->id}: " . $sshException->getMessage();
            }

            if ($configuredVpsCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to configure nginx on any VPS',
                    'errors' => $errors
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully configured nginx on {$configuredVpsCount} VPS instances",
                'configured_vps_count' => $configuredVpsCount,
                'line_username' => $lineUsername,
                'client_domain' => $client->domain,
                'domain_configs' => $domainConfigs,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Error in checkLineStatus: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false, 
                'message' => 'Error checking line status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch lines for a specific client from external API
     */
    public function getClientLines(Request $request, $clientId)
    {
        
        $client = Client::findOrFail($clientId);
        
        try {
            // Fetch lines from client's domain
            $url = 'http://' .$client->domain . '/block_actions.php?action=line';
            
            
            $response = Http::get($url,);
            
            
            if ($response->successful()) {
                $lines = $response->json();
                return response()->json(['success' => true, 'lines' => $lines]);
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to fetch lines'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error fetching lines: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fetch lines from external API and store them in database
     */
    public function fetchAndStoreLines(Request $request, $clientId)
    {
        $client = Client::findOrFail($clientId);
        
        try {
            // Fetch lines from client's domain
            $url = 'http://' . $client->ip . '/block_actions.php?action=line';
            $response = Http::get($url);
            
            if ($response->successful()) {
                $apiLines = $response->json();
                $storedCount = 0;
                $updatedCount = 0;
                
                foreach ($apiLines as $apiLine) {
                    // Check if line already exists
                    $existingLine = Line::where('username', $apiLine['username'])
                                      ->where('client_id', $clientId)
                                      ->first();
                    
                    if ($existingLine) {
                        // Update existing line
                        $existingLine->update([
                            'status' => $apiLine['status'] ?? 'unknown',
                            'password' => $apiLine['password'] ?? $existingLine->password
                        ]);
                        $updatedCount++;
                    } else {
                        // Create new line
                        Line::create([
                            'username' => $apiLine['username'],
                            'password' => $apiLine['password'],
                            'status' => $apiLine['status'] ?? 'unknown',
                            'client_id' => $clientId
                        ]);
                        $storedCount++;
                    }
                }
                
                return response()->json([
                    'success' => true, 
                    'message' => "Lines fetched and stored successfully. New: {$storedCount}, Updated: {$updatedCount}",
                    'stored_count' => $storedCount,
                    'updated_count' => $updatedCount
                ]);
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to fetch lines from API'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error fetching lines: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get stored lines for a specific client from database
     */
    public function getStoredLines(Request $request, $clientId)
    {
        $client = Client::findOrFail($clientId);
        $lines = $client->lines()->orderBy('created_at', 'desc')->get();
        
        return response()->json(['success' => true, 'lines' => $lines]);
    }

    /**
     * Get VPS information for a specific line and client
     */
    public function getLineVps(Request $request, $lineId, $clientId)
    {
        try {
            $vpsInstances = Vps::where('line_id', $lineId)
                               ->where('client_id', $clientId)
                               ->with(['client', 'server'])
                               ->get();
            
            return response()->json(['success' => true, 'vps' => $vpsInstances]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error fetching VPS: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get lines for API or AJAX requests
     */
    public function getLines(Request $request) {
        $clients = Client::all();
        
        if ($request->expectsJson()) {
            return response()->json($clients);
        }
        
        return $clients;
    }
    
    public function test() {
        return response()->json(['message' => 'LinesController is working!']);
    }

    /**
     * Debug method to see all request information
     */
    public function debugRequest(Request $request)
    {
        $debugInfo = [
            'client_ip' => $request->ip(),
            'real_ip' => $request->header('X-Real-IP'),
            'forwarded_for' => $request->header('X-Forwarded-For'),
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'not set',
            'http_client_ip' => $_SERVER['HTTP_CLIENT_IP'] ?? 'not set',
            'http_x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'not set',
            'all_headers' => $request->headers->all(),
            'server_vars' => $_SERVER
        ];
        
        return response()->json($debugInfo);
    }
}
