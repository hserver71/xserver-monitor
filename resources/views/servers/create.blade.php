@extends('layout.app')

@section('title', $selectedClientId ? 'Add Server to Client' : 'Create New Server')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">{{ $selectedClientId ? 'Add Server to Client' : 'Create New Server' }}</h4>
    <div>
        @if($selectedClientId)
            <a href="{{ route('clients.show', $selectedClientId) }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Client
            </a>
        @else
            <a href="{{ route('servers.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        @endif
    </div>
</div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        @if($selectedClientId)
            @php
                $selectedClient = $clients->firstWhere('id', $selectedClientId);
            @endphp
            @if($selectedClient)
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-circle fa-2x me-3"></i>
                        <div>
                            <strong>Adding server for client:</strong><br>
                            <strong>Name:</strong> {{ $selectedClient->name }}<br>
                            <strong>IP:</strong> {{ $selectedClient->ip }}<br>
                            <strong>Domain:</strong> {{ $selectedClient->domain }}
                        </div>
                    </div>
                </div>
            @endif
        @endif
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ $selectedClientId ? 'Add New Server' : 'Create New Server' }}</h5>
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

                <form action="{{ route('servers.store') }}" method="POST" id="create-server-form">
                    @csrf
                    @if($selectedClientId)
                        <input type="hidden" name="client_id" value="{{ $selectedClientId }}">
                    @endif
                    <div class="row mb-3">
                        <div class="{{ $selectedClientId ? 'col-md-12' : 'col-md-6' }}">
                            <label for="name" class="form-label">Server Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                        </div>
                        @if(!$selectedClientId)
                            <div class="col-md-6">
                                <label for="client_id" class="form-label">Client</label>
                                <select class="form-select" id="client_id" name="client_id" required>
                                    <option value="">Select Client</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }} ({{ $client->ip }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <div class="{{ $selectedClientId ? 'col-md-6' : 'col-md-6' }}">
                            <label for="ip" class="form-label">IP Address</label>
                            <input type="text" class="form-control" id="ip" name="ip" value="{{ old('ip') }}" required>
                        </div>
                        <div class="{{ $selectedClientId ? 'col-md-6' : 'col-md-6' }}">
                            <label for="domain" class="form-label">Domain (Optional)</label>
                            <input type="text" class="form-control" id="domain" name="domain" value="{{ old('domain') }}" placeholder="example.com">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        @if($selectedClientId)
                            <a href="{{ route('clients.show', $selectedClientId) }}" class="btn btn-secondary me-2">Back to Client</a>
                        @else
                            <a href="{{ route('servers.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-primary">{{ $selectedClientId ? 'Add Server' : 'Create Server' }}</button>
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
    const form = document.getElementById('create-server-form');
    
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