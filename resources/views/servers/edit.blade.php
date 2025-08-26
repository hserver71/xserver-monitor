@extends('layout.app')

@section('title', 'Edit Server')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">Edit Server</h4>
    <div>
        <a href="{{ route('servers.index') }}" class="btn btn-sm btn-secondary">
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
                <h5 class="mb-0">Edit Server</h5>
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

                <form method="POST" action="{{ route('servers.update', $server) }}" id="edit-server-form">
                    @csrf
                    @method('PUT')
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Server Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $server->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="client_id" class="form-label">Client</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Select Client</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $server->client_id) == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }} ({{ $client->ip }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="ip" class="form-label">IP Address</label>
                            <input type="text" class="form-control" id="ip" name="ip" value="{{ old('ip', $server->ip) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="domain" class="form-label">Domain (Optional)</label>
                            <input type="text" class="form-control" id="domain" name="domain" value="{{ old('domain', $server->domain) }}" placeholder="example.com">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('servers.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Server</button>
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
    const form = document.getElementById('edit-server-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        const name = document.getElementById('name').value;
        const clientId = document.getElementById('client_id').value;
        const ip = document.getElementById('ip').value;
        
        if (!name || !clientId || !ip) {
            alert('Please fill in all required fields');
            return;
        }
        
        // IP validation
        const ipPattern = /^(?!0)(?!.*\.$)((1?\d?\d|25[0-5]|2[0-4]\d)(\.|$)){4}$/;
        if (!ipPattern.test(ip)) {
            alert('Please enter a valid IP address');
            return;
        }
        
        // If validation passes, submit the form
        this.submit();
    });
});
</script>
@endsection 