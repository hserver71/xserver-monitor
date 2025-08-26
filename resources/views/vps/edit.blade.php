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
                            <label for="line_id" class="form-label">Line (Optional)</label>
                            <input type="text" class="form-control" id="line_id" name="line_id" value="{{ old('line_id', $vps->line_id) }}" placeholder="Line ID">
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
        const clientId = document.getElementById('client_id').value;
        const serverId = document.getElementById('server_id').value;
        
        if (!clientId || !serverId) {
            alert('Please select both client and server');
            return;
        }
        
        // If validation passes, submit the form
        this.submit();
    });
});
</script>
@endsection 