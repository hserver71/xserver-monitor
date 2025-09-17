<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vps;
use App\Models\Client;
use App\Models\Server;
use App\Models\Line; // Added this import for Line model
use App\Services\SSHService; // Added this import for SSH service
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VpsController extends Controller
{
    public function index()
    {
        $vps = Vps::with(['client', 'server'])->get();
        $clients = Client::all();
        return view('vps.index', compact('vps', 'clients'));
    }

    public function create()
    {
        $clients = Client::all();
        $servers = Server::all();
        return view('vps.create', compact('clients', 'servers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'server_ip' => ['required', 'string', 'max:45'],
            'serverdomain' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'], // Made required for SSH
            'password' => ['required', 'string', 'max:255'], // Made required for SSH
        ]);

        try {
            $clientId = null;
            
            // Find or create a server with the provided IP
            $server = Server::firstOrCreate(
                ['ip' => $validated['server_ip']],
                [
                    'name' => 'Server ' . $validated['server_ip'],
                    'ip' => $validated['server_ip'],
                    'domain' => $validated['server_ip'],
                ]
            );

            // Create VPS
            $vps = Vps::create([
                'name' => $validated['name'],
                'ip' => $validated['server_ip'], // Store server IP in the ip field
                'server_id' => $server->id,
                'linename' => $validated['linename'] ?? null,
                'serverdomain' => $validated['serverdomain'] ?? null,
                'username' => $validated['username'],
                'password' => $validated['password'],
            ]);

            // Install nginx via SSH
            try {
                Log::info('Starting nginx installation on VPS', [
                    'vps_id' => $vps->id,
                    'ip' => $vps->ip,
                    'username' => $vps->username
                ]);

                $sshService = new SSHService($vps->ip, $vps->username, $vps->password);
                $sshService->connect();
                $nginxStatus = $sshService->checkNginxStatus();
                
                if (!$nginxStatus['installed'] || !$nginxStatus['running']) {
                    // Install nginx if not installed or not running
                    Log::info('Installing nginx on VPS', ['vps_id' => $vps->id, 'ip' => $vps->ip]);
                    $sshService->installNginxProxy();
                    Log::info('Nginx installation completed successfully', ['vps_id' => $vps->id]);
                } else {
                    Log::info('Nginx already installed and running on VPS', ['vps_id' => $vps->id]);
                }
                
                $sshService->disconnect();
                
            } catch (\Exception $sshException) {
                Log::error('SSH/Nginx installation failed', [
                    'vps_id' => $vps->id,
                    'error' => $sshException->getMessage()
                ]);
                
                // VPS was created successfully, but nginx installation failed
                return redirect()->route('vps.index')->with([
                    'status' => 'VPS created successfully, but nginx installation failed: ' . $sshException->getMessage(),
                    'warning' => true
                ]);
            }
            
            return redirect()->route('vps.index')->with('status', 'VPS created successfully and nginx installed.');
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create VPS: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Vps $vps)
    {
        $vps->load(['client', 'server']);
        return view('vps.show', compact('vps'));
    }

    public function edit(Vps $vps)
    {
        $clients = Client::all();
        $servers = Server::all();
        return view('vps.edit', compact('vps', 'clients', 'servers'));
    }

    public function update(Request $request, Vps $vps)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'string', 'max:45'],
            'client_id' => ['required', 'exists:clients,id'],
            'server_id' => ['required', 'exists:servers,id'],
            'domains' => ['nullable', 'string'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'linename' => ['nullable', 'string', 'max:255'],
            'serverdomain' => ['nullable', 'string', 'max:255'],
        ]);

        // Only update password if it's provided
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $vps->update($validated);

        return redirect()->route('vps.index')->with('status', 'VPS updated successfully.');
    }

    public function destroy(Vps $vps)
    {
        try {
            // Start database transaction for data integrity
            DB::beginTransaction();
            Log::info('VPS deletion started', [
                'vps_id' => $vps->id,
                'vps_name' => $vps->name,
                'vps_ip' => $vps->ip,
                'client_id' => $vps->client_id,
                'server_id' => $vps->server_id
            ]);
            // Log the deletion attempt
            Log::info('VPS deletion started', [
                'vps_id' => $vps->id,
                'vps_name' => $vps->name,
                'vps_ip' => $vps->ip,
                'client_id' => $vps->client_id,
                'server_id' => $vps->server_id
            ]);
            
            // Store VPS details for logging
            $vpsDetails = [
                'id' => $vps->id,
                'name' => $vps->name,
                'ip' => $vps->ip,
                'client_id' => $vps->client_id,
                'server_id' => $vps->server_id
            ];
                        
            // Check if VPS has a line assigned and log it
            if ($vps->linename) {
                Log::info('VPS has line assigned, will be unassigned', [
                    'vps_id' => $vps->id,
                    'linename' => $vps->linename
                ]);
            }
            
            // Delete the VPS
            $vps->delete();
            
            // Commit transaction
            DB::commit();
            
            Log::info('VPS deleted successfully', $vpsDetails);
            
            return redirect()->route('vps.index')->with('status', 'VPS "' . ($vpsDetails['name'] ?? 'ID: ' . $vpsDetails['id']) . '" deleted successfully.');
            
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            Log::error('VPS deletion failed', [
                'vps_id' => $vps->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors(['error' => 'Failed to delete VPS: ' . $e->getMessage()]);
        }
    }

    // API Methods for Line Management
    public function getClientLines($clientId)
    {
        try {
            $lines = Line::where('client_id', $clientId)->get();
            $vps = Vps::where('client_id', $clientId)->get();
            
            return response()->json([
                'success' => true,
                'lines' => $lines,
                'vps' => $vps
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching lines: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function assignLineToVpsCore($lineId, $vpsId)
    {
        $line = Line::findOrFail($lineId);
        $vps = Vps::findOrFail($vpsId);
       
        if ($vps->linename) {
            throw new \Exception('VPS already has a line assigned');
        }
        $client = Client::where('id', $line->client_id)->first();
        $servers = Server::where('client_id', $client->id)->get();
        $domainConfigs = [];
        
        foreach ($servers as $server) {
            if ($server->domain) {
                // Split domains by comma and trim whitespace
                $domains = array_map('trim', explode(',', $server->domain));
                
                foreach ($domains as $domain) {
                    if ($domain) {
                        if (str_contains($domain, '*')) {
                            if (str_starts_with($domain, '*.')) {
                                // Remove *. from the beginning
                                $cleanDomain = ltrim($domain, '*.');
                                $domainConfigs[] = [
                                    'main_domain' => $domain,
                                    'proxy' => $server->ip
                                ];
                            }
                        } 
                    }
                }
            }
        }

        if (empty($domainConfigs)) {
            throw new \Exception('No valid domains found for configuration');
        }

        // Check VPS nginx status and configure
        $sshService = new \App\Services\SSHService($vps->ip, $vps->username, $vps->password);
        $sshService->connect();

        // Check nginx status
        $nginxStatus = $sshService->checkNginxStatus();
        
        if (!$nginxStatus['installed'] || !$nginxStatus['running']) {
            Log::info('Installing nginx on VPS', ['vps_id' => $vps->id, 'ip' => $vps->ip]);
            $sshService->installNginxProxy();
        }

        // Configure nginx with domain configurations
        Log::info('Configuring nginx with domain configs', [
            'vps_id' => $vps->id,
            'domain_configs' => $domainConfigs
        ]);
        
        $sshService->configureNginxWithDomainConfigs($domainConfigs);
        $sshService->disconnect();

        // Update line and VPS records
        $line->update([
            'assigned_at' => now()
        ]);
        $vps->update([
            'linename' => $line->username,
            'serverdomain' => $line->username . '.' . $client->domain,
            'domains' => json_encode($domainConfigs),
            'assigned_at' => now()
        ]);

        return [
            'success' => true,
            'message' => 'Line assigned to VPS successfully and nginx configured',
            'domain_configs' => $domainConfigs
        ];
    }

    public function assignLineToVps(Request $request)
    {
        $validated = $request->validate([
            'line_id' => 'required|exists:lines,id',
            'vps_id' => 'required|exists:vps,id'
        ]);
        
        try {
            $result = $this->assignLineToVpsCore($validated['line_id'], $validated['vps_id']);
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('SSH/Nginx configuration failed', [
                'vps_id' => $validated['vps_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error assigning line: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unassign line from VPS
     */
    public function unassignLineFromVps(Request $request)
    {
        try {
            $validated = $request->validate([
                'vps_id' => 'required|exists:vps,id'
            ]);

            $vps = Vps::findOrFail($validated['vps_id']);

            // Clear line information from VPS
            // Find and clear line assignment information
            $line = Line::where('username', $vps->linename)->first();
            $vps->update([
                'linename' => null,
                'serverdomain' => null,
                'domains' => null,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Line unassigned from VPS successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error unassigning line: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getVps()
    {
        $vps = Vps::all();
        return response()->json([
            'success' => true,
            'vps' => $vps
        ]);
    }
    /**
     * Configure nginx on VPS with domain configurations
     */
    public function configureNginxOnVps(Request $request)
    {
        try {
            $validated = $request->validate([
                'domain_config' => 'required|array',
                'domain_config.main_domain' => 'required|string',
                'domain_config.proxy' => 'required|string'
            ]);

            // Get the first VPS from the array
            $vpsInstances = Vps::all();
            if ($vpsInstances->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No VPS instances found'
                ], 404);
            }

            $vps = $vpsInstances->first();

            // Check if VPS has required credentials
            if (!$vps->ip || !$vps->username || !$vps->password) {
                return response()->json([
                    'success' => false,
                    'message' => 'VPS missing required credentials (IP, username, or password)'
                ], 400);
            }

            // Configure nginx on VPS
            try {
                $sshService = new \App\Services\SSHService($vps->ip, $vps->username, $vps->password);
                $sshService->connect();

                // Check nginx status
                $nginxStatus = $sshService->checkNginxStatus();
                
                if (!$nginxStatus['installed'] || !$nginxStatus['running']) {
                    // Install nginx if not installed or not running
                    \Illuminate\Support\Facades\Log::info('Installing nginx on VPS', ['vps_id' => $vps->id, 'ip' => $vps->ip]);
                    $sshService->installNginxProxy();
                }

                // Configure nginx with domain configurations
                \Illuminate\Support\Facades\Log::info('Configuring nginx with domain configs', [
                    'vps_id' => $vps->id,
                    'domain_configs' => $validated['domain_configs']
                ]);
                
                $sshService->configureNginxWithDomainConfigs($validated['domain_configs']);
                
                $sshService->disconnect();
                
            } catch (\Exception $sshException) {
                \Illuminate\Support\Facades\Log::error('SSH/Nginx configuration failed', [
                    'vps_id' => $vps->id,
                    'error' => $sshException->getMessage()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to configure nginx: ' . $sshException->getMessage()
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Nginx configured successfully on VPS',
                'vps_used' => [
                    'id' => $vps->id,
                    'name' => $vps->name,
                    'ip' => $vps->ip
                ],
                'domain_configs' => $validated['domain_configs']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error configuring nginx: ' . $e->getMessage()
            ], 500);
        }
    }
}
