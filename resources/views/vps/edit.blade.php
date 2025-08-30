@extends('layout.app')

@section('title', 'Edit VPS')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">Edit VPS</h4>
    <div>
        <a href="{{ route('vps.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit VPS</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('vps.update', $vps) }}" id="edit-vps-form">
                    @csrf
                    @method('PUT')
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">VPS Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $vps->name) }}" required placeholder="Enter VPS name">
                        </div>
                        <div class="col-md-6">
                            <label for="ip" class="form-label">Server IP</label>
                            <input type="text" class="form-control" id="ip" name="ip" value="{{ old('ip', $vps->ip) }}" required placeholder="Enter server IP address">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label">Client</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Select Client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $vps->client_id) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }} ({{ $client->ip }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="server_id" class="form-label">Server</label>
                            <select class="form-select" id="server_id" name="server_id" required>
                                <option value="">Select Server</option>
                                @foreach($servers as $server)
                                    <option value="{{ $server->id }}" {{ old('server_id', $vps->server_id) == $server->id ? 'selected' : '' }}>
                                        {{ $server->name }} ({{ $server->ip }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="linename" class="form-label">Line Name (Optional)</label>
                            <input type="text" class="form-control" id="linename" name="linename" value="{{ old('linename', $vps->linename) }}" placeholder="Enter line name">
                        </div>
                        <div class="col-md-6">
                            <label for="serverdomain" class="form-label">Server Domain (Optional)</label>
                            <input type="text" class="form-control" id="serverdomain" name="serverdomain" value="{{ old('serverdomain', $vps->serverdomain) }}" placeholder="Enter server domain">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">Username (Optional)</label>
                            <input type="text" class="form-control" id="username" name="username" value="{{ old('username', $vps->username) }}" placeholder="Username">
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password (Optional)</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="domains" class="form-label">Domains (Optional)</label>
                            <input type="text" class="form-control" id="domains" name="domains" value="{{ old('domains', $vps->domains) }}" placeholder="Comma separated domains">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('vps.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update VPS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-vps-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        const name = document.getElementById('name').value.trim();
        const ip = document.getElementById('ip').value.trim();
        const clientId = document.getElementById('client_id').value;
        const serverId = document.getElementById('server_id').value;
        
        if (!name || !ip || !clientId || !serverId) {
            alert('Please fill in all required fields (VPS Name, Server IP, Client, and Server)');
            return;
        }
        
        // Basic IP validation
        const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        if (!ipRegex.test(ip)) {
            alert('Please enter a valid IP address');
            return;
        }
        
        // If validation passes, submit the form
        this.submit();
    });
});
</script>
@endsection 