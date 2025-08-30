<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vps;
use App\Models\Client;
use App\Models\Server;
use App\Models\Line; // Added this import for Line model

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
            'server_ip' => ['required', 'string', 'max:45'], // IPv6 can be up to 45 chars
            'client_id' => ['required', 'exists:clients,id'],
            'linename' => ['nullable', 'string', 'max:255'],
            'serverdomain' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            // Find or create a default client if not provided
            if (empty($validated['client_id'])) {
                $client = Client::firstOrCreate(
                    ['name' => 'Default Client'],
                    [
                        'name' => 'Default Client',
                        'ip' => '127.0.0.1',
                        'domain' => 'default.local'
                    ]
                );
                $validated['client_id'] = $client->id;
            }

            // Find or create a server with the provided IP
            $server = Server::firstOrCreate(
                ['ip' => $validated['server_ip']],
                [
                    'name' => 'Server ' . $validated['server_ip'],
                    'ip' => $validated['server_ip'],
                    'domain' => $validated['server_ip'],
                    'client_id' => $validated['client_id']
                ]
            );

            // Create VPS
            $vps = Vps::create([
                'name' => $validated['name'],
                'ip' => $validated['server_ip'], // Store server IP in the ip field
                'client_id' => $validated['client_id'],
                'server_id' => $server->id,
                'linename' => $validated['linename'] ?? null,
                'serverdomain' => $validated['serverdomain'] ?? null,
                'username' => $validated['username'] ?? null,
                'password' => $validated['password'] ?? null,
            ]);
            
            return redirect()->route('vps.index')->with('status', 'VPS created successfully.');
            
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
            $vps->delete();
            return redirect()->route('vps.index')->with('status', 'VPS deleted successfully.');
        } catch (\Exception $e) {
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

    public function assignLineToVps(Request $request)
    {
        try {
            $validated = $request->validate([
                'line_id' => 'required|exists:lines,id',
                'vps_id' => 'required|exists:vps,id'
            ]);

            $line = Line::findOrFail($validated['line_id']);
            $vps = Vps::findOrFail($validated['vps_id']);

            // Check if VPS already has a line assigned
            if ($vps->linename) {
                return response()->json([
                    'success' => false,
                    'message' => 'VPS already has a line assigned'
                ], 400);
            }

            // Update VPS with line information
            $vps->update([
                'linename' => $line->username,
                'serverdomain' => $line->username . '.' . $vps->client->domain
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Line assigned to VPS successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning line: ' . $e->getMessage()
            ], 500);
        }
    }

    public function unassignLineFromVps(Request $request)
    {
        try {
            $validated = $request->validate([
                'line_id' => 'required|exists:lines,id',
                'vps_id' => 'required|exists:vps,id'
            ]);

            $vps = Vps::findOrFail($validated['vps_id']);

            // Clear line information from VPS
            $vps->update([
                'linename' => null,
                'serverdomain' => null
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
}
