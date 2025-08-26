@extends('layout.app')

@section('title', 'Client Details')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">Client Details</h4>
    <div>
        <a href="{{ route('clients.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Clients
        </a>
        <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-edit me-1"></i> Edit Client
        </a>
        <a href="{{ route('servers.create') }}?client_id={{ $client->id }}" class="btn btn-sm btn-success">
            <i class="fas fa-plus me-1"></i> Add Server
        </a>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@endsection

@section('content')
<div class="row">
    <!-- Client Information -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Client Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Name:</strong>
                    <p class="mb-0">{{ $client->name }}</p>
                </div>
                <div class="mb-3">
                    <strong>IP Address:</strong>
                    <p class="mb-0">{{ $client->ip }}</p>
                </div>
                <div class="mb-3">
                    <strong>Domain:</strong>
                    <p class="mb-0">{{ $client->domain }}</p>
                </div>
                <div class="mb-3">
                    <strong>Created:</strong>
                    <p class="mb-0">{{ $client->created_at->format('M d, Y H:i') }}</p>
                </div>
                <div class="mb-3">
                    <strong>Last Updated:</strong>
                    <p class="mb-0">{{ $client->updated_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <button class="btn btn-success w-100" onclick="fetchServersFromAPI()">
                    <i class="fas fa-sync-alt me-1"></i> Fetch Servers from API
                </button>
            </div>
        </div>
    </div>

    <!-- Servers List -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Client Servers</h5>
                <div>
                    <span class="badge bg-info me-2">{{ $client->servers->count() }} servers</span>
                    <button class="btn btn-sm btn-outline-primary" onclick="refreshServers()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if($client->servers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Server Name</th>
                                    <th>IP Address</th>
                                    <th>Domain</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($client->servers as $server)
                                    <tr>
                                        <td>{{ $server->name }}</td>
                                        <td>{{ $server->ip }}</td>
                                        <td>{{ $server->domain ?? 'N/A' }}</td>
                                        <td>{{ $server->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('servers.edit', $server) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteServer({{ $server->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-server fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No servers found for this client.</p>
                        <button class="btn btn-primary" onclick="fetchServersFromAPI()">
                            <i class="fas fa-sync-alt me-1"></i> Fetch from API
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 id="loading-message">Fetching servers from client domain...</h5>
                <p class="text-muted">This may take a few moments</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let selectedClient = @json($client);

function fetchServersFromAPI() {
    // Show loading modal
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    loadingModal.show();
    
    // Update loading message
    document.getElementById('loading-message').textContent = `Fetching servers from ${selectedClient.domain}...`;
    
    // Make API call to fetch servers
    const requestBody = {
        client_id: selectedClient.id
    };
    
    fetch('/api/fetch-servers', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(requestBody)
    })
    .then(response => response.json())
    .then(data => {
        loadingModal.hide();
        
        if (data.success) {
            // Show success message
            const message = `Servers fetched successfully! Created: ${data.data.created}, Updated: ${data.data.updated}, Removed: ${data.data.removed}, Duplicates removed: ${data.data.duplicates_removed}`;
            showAlert(message, 'success');
            
            // Reload the page to show updated servers
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            let errorMessage = data.message || 'Unknown error';
            
            // Add troubleshooting tips for common errors
            if (data.message && data.message.includes('404')) {
                errorMessage += '\n\n💡 Troubleshooting:\n• Check if the endpoint exists on the client server\n• Verify the domain is correct';
            } else if (data.message && data.message.includes('403')) {
                errorMessage += '\n\n💡 Troubleshooting:\n• This might be a CORS issue\n• Check server configuration';
            } else if (data.message && data.message.includes('Connection failed')) {
                errorMessage += '\n\n💡 Troubleshooting:\n• Check if client server is online\n• Verify domain/IP is accessible';
            }
            
            showAlert(errorMessage, 'danger');
        }
    })
    .catch(error => {
        loadingModal.hide();
        console.error('Error:', error);
        showAlert('Error fetching servers. Please try again.', 'danger');
    });
}

function refreshServers() {
    window.location.reload();
}

function deleteServer(serverId) {
    if (confirm('Are you sure you want to delete this server?')) {
        fetch(`/servers/${serverId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Server deleted successfully!', 'success');
                // Reload the page
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert('Error deleting server: ' + (data.message || 'Unknown error'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error deleting server. Please try again.', 'danger');
        });
    }
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert after header
    const header = document.querySelector('.header-bar');
    header.parentNode.insertBefore(alertDiv, header.nextSibling);
    
    // Auto remove after 8 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 8000);
}
</script>
@endpush 