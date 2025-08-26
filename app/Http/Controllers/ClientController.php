<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::all();
        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'ip'],
            'domain' => ['required', 'string', 'max:255']
        ]);

        Client::create($validated);
        
        return redirect()->route('clients.index')->with('status', 'Client added successfully.');
    }

    public function show(Client $client)
    {
        $client->load('servers', 'vps');
        return view('clients.show', compact('client'));
    }
    public function getClient(Request $request) {
        
    }
    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'ip'],
            'domain' => ['required', 'string', 'max:255']
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')->with('status', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('status', 'Client deleted successfully.');
    }
}