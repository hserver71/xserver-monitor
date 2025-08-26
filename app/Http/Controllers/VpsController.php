<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vps;
use App\Models\Client;
use App\Models\Server;

class VpsController extends Controller
{
    public function index()
    {
        $vps = Vps::with(['client', 'server'])->get();
        return view('vps.index', compact('vps'));
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
            'client_id' => ['required', 'exists:clients,id'],
            'server_id' => ['required', 'exists:servers,id'],
            'line_id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'ip' => ['nullable', 'ip'],
            'domains' => ['nullable', 'string'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'linename' => ['nullable', 'string', 'max:255'],
            'serverdomain' => ['nullable', 'string', 'max:255'],
        ]);

        Vps::create($validated);
        
        return redirect()->route('vps.index')->with('status', 'VPS created successfully.');
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
            'client_id' => ['required', 'exists:clients,id'],
            'server_id' => ['required', 'exists:servers,id'],
            'line_id' => ['nullable', 'integer'],
            'name' => ['nullable', 'string', 'max:255'],
            'ip' => ['nullable', 'ip'],
            'domains' => ['nullable', 'string'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'linename' => ['nullable', 'string', 'max:255'],
            'serverdomain' => ['nullable', 'string', 'max:255'],
        ]);

        $vps->update($validated);

        return redirect()->route('vps.index')->with('status', 'VPS updated successfully.');
    }

    public function destroy(Vps $vps)
    {
        $vps->delete();
        return redirect()->route('vps.index')->with('status', 'VPS deleted successfully.');
    }
}
