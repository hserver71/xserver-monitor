<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Vps;
use App\Models\Line;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LinesController extends Controller
{
    /**
     * Display the lines page with clients, lines, and VPS sections
     */
    public function index()
    {
        $clients = Client::all();
        return view('lines.index', compact('clients'));
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

            // Parse origin address to get client domain
            $origin = $request->header('Origin') ?? $request->header('Referer');
            
            if (!$origin) {
                return response()->json(['success' => false, 'message' => 'Origin header not found'], 400);
            }

            // Extract domain from origin (remove protocol and port)
            $parsedUrl = parse_url($origin);
            $clientDomain = $parsedUrl['host'] ?? null;
            
            if (!$clientDomain) {
                return response()->json(['success' => false, 'message' => 'Could not parse client domain from origin'], 400);
            }

            Log::info('Client domain from origin: ' . $clientDomain);

            // Find client by domain
            $client = Client::where('domain', $clientDomain)->first();
            
            if (!$client) {
                return response()->json(['success' => false, 'message' => 'Client not found for domain: ' . $clientDomain], 404);
            }

            Log::info('Client found: ' . $client->name . ' (ID: ' . $client->id . ')');

            // Find line by username and client
            $line = Line::where('username', $lineUsername)
                       ->where('client_id', $client->id)
                       ->first();
            
            if (!$line) {
                return response()->json(['success' => false, 'message' => 'Line not found for username: ' . $lineUsername], 404);
            }

            Log::info('Line found: ' . $line->username . ' (ID: ' . $line->id . ')');

            // Find VPS instances with empty domain fields for this client
            $vpsInstances = Vps::where('client_id', $client->id)
                               ->where(function($query) {
                                   $query->whereNull('domains')
                                         ->orWhere('domains', '')
                                         ->orWhere('domains', '[]')
                                         ->orWhere('domains', '[""]')
                                         ->orWhere('domains', '{}');
                               })
                               ->get();

            Log::info('Found ' . $vpsInstances->count() . ' VPS instances with empty domains');
            
            // Log the VPS instances for debugging
            foreach ($vpsInstances as $vps) {
                Log::info('VPS ' . $vps->id . ' - domains: ' . ($vps->domains ?? 'NULL'));
            }

            if ($vpsInstances->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No VPS instances with empty domains found'], 404);
            }

            // Assign domains to VPS instances
            $assignedCount = 0;
            foreach ($vpsInstances as $vps) {
                try {
                    // Create domain array: [linename + client domain]
                    $domainArray = [$lineUsername . '.' . $client->domain];
                    
                    Log::info('Assigning domain to VPS ' . $vps->id . ': ' . json_encode($domainArray));
                    
                    // Update VPS with domain array using DB transaction for safety
                    DB::beginTransaction();
                    
                    $vps->domains = json_encode($domainArray);
                    $vps->save();
                    
                    DB::commit();
                    
                    $assignedCount++;
                    Log::info('Successfully updated VPS ' . $vps->id . ' with domains: ' . $vps->domains);
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Error updating VPS ' . $vps->id . ': ' . $e->getMessage());
                    Log::error('Stack trace: ' . $e->getTraceAsString());
                    continue;
                }
            }

            Log::info('Successfully assigned domains to ' . $assignedCount . ' VPS instances');

            return response()->json([
                'success' => true,
                'message' => "Successfully assigned domains to {$assignedCount} VPS instances",
                'assigned_count' => $assignedCount,
                'line_username' => $lineUsername,
                'client_domain' => $client->domain,
                'assigned_domains' => $lineUsername . '.' . $client->domain
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
            $url = 'http://' . $client->domain . '/block_actions.php?action=line';
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
}
