@extends('layout.app')

@section('title', 'VPS Management')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">VPS Management</h4>
    <div>
        <a href="{{ route('vps.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Add VPS
        </a>
    </div>
</div>
@endsection

@section('content')
<!-- VPS List Section -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">VPS Instances</h5>
    </div>
    <div class="card-body">
        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($vps->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>VPS Name</th>
                            <th>Server IP</th>
                            <th>Client</th>
                            <th>Server</th>
                            <th>Line Name</th>
                            <th>Server Domain</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vps as $vpsInstance)
                            <tr>
                                <td>{{ $vpsInstance->id }}</td>
                                <td>
                                    <strong>{{ $vpsInstance->name ?? 'N/A' }}</strong>
                                </td>
                                <td>
                                    <code>{{ $vpsInstance->ip ?? 'N/A' }}</code>
                                </td>
                                <td>{{ $vpsInstance->client->name ?? 'N/A' }}</td>
                                <td>{{ $vpsInstance->server->name ?? 'N/A' }}</td>
                                <td>
                                    @if($vpsInstance->linename)
                                        <span class="badge bg-info">{{ $vpsInstance->linename }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($vpsInstance->serverdomain)
                                        <code>{{ $vpsInstance->serverdomain }}</code>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $vpsInstance->created_at->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-success test-ssh-btn" 
                                                data-vps-id="{{ $vpsInstance->id }}" 
                                                data-vps-ip="{{ $vpsInstance->ip }}" 
                                                data-vps-username="{{ $vpsInstance->username }}"
                                                title="Test SSH Connection">
                                            <i class="fas fa-terminal"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary install-nginx-btn" 
                                                data-vps-id="{{ $vpsInstance->id }}" 
                                                data-vps-ip="{{ $vpsInstance->ip }}" 
                                                data-vps-username="{{ $vpsInstance->username }}"
                                                title="Install Nginx">
                                            <i class="fas fa-server"></i>
                                        </button>
                                        <a href="{{ route('vps.show', $vpsInstance) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('vps.edit', $vpsInstance) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('vps.destroy', $vpsInstance) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this VPS?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4">
                <p class="text-muted">No VPS instances found.</p>
                <a href="{{ route('vps.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Your First VPS
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Test SSH Connection
    $('.test-ssh-btn').on('click', function() {
        const vpsId = $(this).data('vps-id');
        const vpsIp = $(this).data('vps-ip');
        const vpsUsername = $(this).data('vps-username');
        
        if (!vpsUsername) {
            alert('VPS missing username. Please edit the VPS to add SSH credentials.');
            return;
        }
        
        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i>');
        btn.prop('disabled', true);
        
        $.ajax({
            url: `/vps/${vpsId}/test-ssh`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert(`SSH Connection Successful!\n\nServer Info:\n${response.server_info.system_info}\n\nNginx Status: ${response.nginx_status.running ? 'Running' : 'Not Running'}`);
                } else {
                    alert('SSH Connection Failed: ' + response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                alert('SSH Connection Failed: ' + (response?.message || 'Unknown error'));
            },
            complete: function() {
                btn.html(originalHtml);
                btn.prop('disabled', false);
            }
        });
    });
    
    // Install Nginx
    $('.install-nginx-btn').on('click', function() {
        const vpsId = $(this).data('vps-id');
        const vpsIp = $(this).data('vps-ip');
        const vpsUsername = $(this).data('vps-username');
        
        if (!vpsUsername) {
            alert('VPS missing username. Please edit the VPS to add SSH credentials.');
            return;
        }
        
        if (!confirm(`Install nginx proxy on ${vpsIp}? This will connect via SSH and install nginx.`)) {
            return;
        }
        
        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i>');
        btn.prop('disabled', true);
        
        $.ajax({
            url: `/vps/${vpsId}/install-nginx`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Nginx installed successfully!');
                } else {
                    alert('Nginx installation failed: ' + response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                alert('Nginx installation failed: ' + (response?.message || 'Unknown error'));
            },
            complete: function() {
                btn.html(originalHtml);
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
