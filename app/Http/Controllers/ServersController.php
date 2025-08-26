<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Server;
use App\Models\Client;

class ServersController extends Controller
{
    public function index()
    {
        $servers = Server::with('client')->get();
        return view('servers.index', compact('servers'));
    }

    public function create()
    {
        $clients = Client::all();
        $selectedClientId = request('client_id');
        return view('servers.create', compact('clients', 'selectedClientId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'ip'],
            'domain' => ['nullable', 'string', 'max:255'],
            'client_id' => ['required', 'exists:clients,id'],
        ]);

        Server::create($validated);
        
        // Redirect back to client if coming from client page
        if ($request->has('client_id')) {
            return redirect()->route('clients.show', $request->client_id)->with('status', 'Server created successfully.');
        }
        
        return redirect()->route('servers.index')->with('status', 'Server created successfully.');
    }

    public function show(Server $server)
    {
        $server->load('client');
        return view('servers.show', compact('server'));
    }

    public function edit(Server $server)
    {
        $clients = Client::all();
        return view('servers.edit', compact('server', 'clients'));
    }

    public function update(Request $request, Server $server)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'ip'],
            'domain' => ['nullable', 'string', 'max:255'],
            'client_id' => ['required', 'exists:clients,id'],
        ]);

        $server->update($validated);

        return redirect()->route('servers.index')->with('status', 'Server updated successfully.');
    }

    public function destroy(Server $server)
    {
        try {
            $server->delete();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Server deleted successfully.'
                ]);
            }
            
            return redirect()->route('servers.index')->with('status', 'Server deleted successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting server: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->route('servers.index')->with('error', 'Error deleting server.');
        }
    }
}
