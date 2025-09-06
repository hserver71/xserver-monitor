@extends('layout.app')

@section('title', 'Create New VPS')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">Create New VPS</h4>
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
                <h5 class="mb-0">Create New VPS</h5>
                <small class="text-muted">SSH connection will be established to install nginx proxy</small>
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

                <form action="{{ route('vps.store') }}" method="POST" id="create-vps-form">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">VPS Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required placeholder="Enter VPS name">
                        </div>
                        <div class="col-md-6">
                            <label for="server_ip" class="form-label">Server IP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="server_ip" name="server_ip" value="{{ old('server_ip') }}" required placeholder="Enter server IP address">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">SSH Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required placeholder="Enter SSH username">
                            <small class="form-text text-muted">Username for SSH connection</small>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">SSH Password <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="password" name="password" required placeholder="Enter SSH password">
                            <small class="form-text text-muted">Password for SSH connection</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="serverdomain" class="form-label">Server Domain (Optional)</label>
                            <input type="text" class="form-control" id="serverdomain" name="serverdomain" value="{{ old('serverdomain') }}" placeholder="Enter server domain">
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> After creating the VPS, the system will automatically connect via SSH and install nginx as a proxy server.
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('vps.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Create VPS & Install Nginx
                        </button>
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
    const form = document.getElementById('create-vps-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        const name = document.getElementById('name').value.trim();
        const serverIp = document.getElementById('server_ip').value.trim();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        
        if (!name || !serverIp || !username || !password) {
            alert('Please fill in all required fields (VPS Name, Server IP, Username, and Password)');
            return;
        }
        
        // Basic IP validation
        const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
        if (!ipRegex.test(serverIp)) {
            alert('Please enter a valid IP address');
            return;
        }
        
        // Show confirmation
        if (!confirm('This will create a VPS and install nginx proxy via SSH. Continue?')) {
            return;
        }
        
        // If validation passes, submit the form
        this.submit();
    });
});
</script>
@endsection
