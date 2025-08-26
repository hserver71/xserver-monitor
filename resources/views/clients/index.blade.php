@extends('layout.app')

@section('title', 'Client Management')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">Client Management</h4>
    <div>
        <a href="{{ route('clients.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> New Client
        </a>
        <button class="btn btn-sm btn-success ms-2" id="fetch-servers-btn">
            <i class="fas fa-sync-alt me-1"></i> Fetch Servers
        </button>
        <button class="btn btn-sm btn-warning ms-2" id="test-fetch-servers-btn">
            <i class="fas fa-flask me-1"></i> Test Mode
        </button>
        <button class="btn btn-sm btn-secondary ms-2" id="debug-connection-btn">
            <i class="fas fa-bug me-1"></i> Debug
        </button>
        <button class="btn btn-sm btn-info ms-2" id="fetch-all-servers-btn">
            <i class="fas fa-globe me-1"></i> Fetch All Clients
        </button>
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
    <!-- Left Column - Clients List -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Clients</h5>
                <span class="badge bg-primary">{{ $clients->count() }}</span>
            </div>
            <div class="card-body p-0">
                @if($clients->count() > 0)
                    <ul class="list-group list-group-flush" id="clients-list">
                        @foreach($clients as $client)
                            <li class="list-group-item client-item" data-client-id="{{ $client->id }}" style="cursor: pointer;" onclick="selectClient({{ $client->id }})">
                                <div class="d-flex justify-content-between align-items-center" style="width:100%">
                                    <div>
                                        <h6 class="mb-1">{{ $client->name }}</h6>
                                        <small class="text-muted">{{ $client->ip }} • {{ $client->domain }}</small>
                                    </div>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('clients.destroy', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this client?')" onclick="event.stopPropagation();">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted">No clients found.</p>
                        <a href="{{ route('clients.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add Your First Client
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Right Column - Client Details & Servers -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="client-details-title">Select a client to view details</h5>
                <div id="client-actions" style="display: none;">
                    <button class="btn btn-sm btn-success" id="add-server-btn">
                        <i class="fas fa-plus me-1"></i> Add Server
                    </button>
                </div>
            </div>
            <div class="card-body" id="client-details-content">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-user-circle fa-3x mb-3"></i>
                    <p>Select a client from the list to view details and servers</p>
                </div>
            </div>
        </div>
        
        <!-- Servers Information Card -->
        <div class="card" id="servers-card" style="display: none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Client Servers</h5>
                <div>
                    <span class="badge bg-info me-2" id="servers-count">0</span>
                    <button class="btn btn-sm btn-outline-primary" id="refresh-servers-btn">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body" id="servers-content">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading servers...</p>
                </div>
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

@section('styles')
<style>
</style>
@endsection

@push('scripts')
<script>
// Global variables
let selectedClientId = null;
let selectedClient = null;

// Define selectClient function first (before it's used in HTML)
function selectClient(clientId) {
    console.log('=== selectClient called ===');
    console.log('Client ID received:', clientId);
    console.log('Type of clientId:', typeof clientId);
    
    // Remove active class from all clients
    document.querySelectorAll('.client-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active class to selected client
    const selectedItem = document.querySelector(`[data-client-id="${clientId}"]`);
    if (selectedItem) {
        selectedItem.classList.add('active');
        console.log('✅ Active class added to client item');
    } else {
        console.error('❌ Could not find client item with ID:', clientId);
        console.log('Available client items:', document.querySelectorAll('.client-item').length);
        return;
    }
    
    selectedClientId = clientId;
    console.log('✅ selectedClientId set to:', selectedClientId);
    
    // Get client data from the DOM
    const clientName = selectedItem.querySelector('h6').textContent;
    const clientIp = selectedItem.querySelector('small').textContent.split(' • ')[0];
    const clientDomain = selectedItem.querySelector('small').textContent.split(' • ')[1];
    
    console.log('✅ Client data extracted:', { clientName, clientIp, clientDomain });
    
    selectedClient = {
        id: clientId,
        name: clientName,
        ip: clientIp,
        domain: clientDomain
    };
    
    console.log('✅ selectedClient object created:', selectedClient);
    
    // Show client details
    showClientDetails();
    
    // Load client servers
    loadClientServers(clientId);
    
    console.log('=== selectClient completed ===');
}

// Define other functions
function showClientDetails() {
    if (!selectedClient) return;
    
    // Update header
    document.getElementById('client-details-title').textContent = `${selectedClient.name} - Details`;
    
    // Show action buttons
    document.getElementById('client-actions').style.display = 'block';
    
    // Update content
    const content = `
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <strong>Client Name:</strong> ${selectedClient.name}
                </div>
                <div class="mb-3">
                    <strong>IP Address:</strong> ${selectedClient.ip}
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <strong>Domain:</strong> ${selectedClient.domain}
                </div>
                <div class="mb-3">
                    <strong>Status:</strong> <span class="badge bg-success">Active</span>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('client-details-content').innerHTML = content;
}

function loadClientServers(clientId) {
    // Show servers card
    document.getElementById('servers-card').style.display = 'block';
    
    // Show loading state
    document.getElementById('servers-content').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading servers...</p>
        </div>
    `;
    
    // Fetch servers from database for this client
    fetch(`/api/clients/${clientId}/servers`)
        .then(response => response.json())
        .then(data => {
            displayClientServers(data.servers || []);
        })
        .catch(error => {
            console.error('Error loading servers:', error);
            document.getElementById('servers-content').innerHTML = `
                <div class="text-center py-4">
                    <div class="text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Error loading servers. Please try again.</p>
                    </div>
                </div>
            `;
        });
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing client selection...');
    
    // Check if client items exist
    const clientItems = document.querySelectorAll('.client-item');
    console.log('Client items found on DOM ready:', clientItems.length);
    
    // Initialize client selection
    initializeClientSelection();
    
    // Initialize fetch servers button
    initializeFetchServersButton();
    
    // Initialize fetch all servers button
    initializeFetchAllServersButton();
    
    // Initialize refresh servers button
    initializeRefreshServersButton();
    
    console.log('All initialization complete');
    
    // Double-check after a short delay
    setTimeout(() => {
        const finalCheck = document.querySelectorAll('.client-item');
        console.log('Final check - Client items found:', finalCheck.length);
        if (finalCheck.length > 0) {
            console.log('First client item:', finalCheck[0]);
            console.log('First client ID:', finalCheck[0].getAttribute('data-client-id'));
        }
    }, 500);
});

function initializeClientSelection() {
    console.log('Direct event listeners already added via onclick attributes');
    
    // Verify that selectClient function is available globally
    if (typeof selectClient === 'function') {
        console.log('selectClient function is available');
    } else {
        console.error('selectClient function is not available!');
    }
}

// Duplicate functions removed - they are now defined at the top of the script

function displayClientServers(servers) {
    const serversCount = servers.length;
    document.getElementById('servers-count').textContent = serversCount;
    
    if (serversCount === 0) {
        document.getElementById('servers-content').innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-server fa-2x text-muted mb-3"></i>
                <p class="text-muted">No servers found for this client.</p>
                <button class="btn btn-primary" onclick="fetchServersFromAPI()">
                    <i class="fas fa-sync-alt me-1"></i> Fetch from API
                </button>
            </div>
        `;
        return;
    }
    
    let tableHtml = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Server Name</th>
                        <th>IP Address</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    servers.forEach(server => {
        tableHtml += `
            <tr>
                <td>${server.name}</td>
                <td>${server.ip}</td>
                <td>${server.domain || 'N/A'}</td>
                <td><span class="badge bg-success">Active</span></td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="/servers/${server.id}/edit" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteServer(${server.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    tableHtml += `
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>Showing ${serversCount} server(s)</div>
            <button class="btn btn-outline-primary" onclick="fetchServersFromAPI()">
                <i class="fas fa-sync-alt me-1"></i> Refresh from API
            </button>
        </div>
    `;
    
    document.getElementById('servers-content').innerHTML = tableHtml;
}

function initializeFetchServersButton() {
    document.getElementById('fetch-servers-btn').addEventListener('click', function() {
        if (selectedClientId) {
            // If a client is selected, fetch for that client
            fetchServersFromAPI();
        } else {
            // If no client selected, show message
            showAlert('Please select a client first to fetch servers', 'info');
        }
    });

    // Initialize test mode button
    const testFetchBtn = document.getElementById('test-fetch-servers-btn');
    if (testFetchBtn) {
        testFetchBtn.addEventListener('click', function() {
            if (selectedClientId) {
                // If a client is selected, fetch for that client in test mode
                fetchServersFromAPI(true); // true = test mode
            } else {
                // If no client selected, show message
                showAlert('Please select a client first to test fetch servers', 'info');
            }
        });
    }

    // Initialize debug button
    const debugBtn = document.getElementById('debug-connection-btn');
    if (debugBtn) {
        debugBtn.addEventListener('click', function() {
            if (selectedClientId) {
                debugConnection(selectedClientId);
            } else {
                showAlert('Please select a client first to debug connection', 'info');
            }
        });
    }
}

function initializeFetchAllServersButton() {
    document.getElementById('fetch-all-servers-btn').addEventListener('click', function() {
        fetchAllClientsServers();
    });
}

function initializeRefreshServersButton() {
    document.getElementById('refresh-servers-btn').addEventListener('click', function() {
        if (selectedClientId) {
            loadClientServers(selectedClientId);
        }
    });
}

function fetchServersFromAPI(testMode = false) {
    if (!selectedClientId) {
        showAlert('Please select a client first', 'warning');
        return;
    }

    // Show loading modal
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    loadingModal.show();
    
    // Update loading message
    const modeText = testMode ? 'TEST MODE - ' : '';
    document.getElementById('loading-message').textContent = `${modeText}Fetching servers from ${selectedClient.domain}...`;
    
    // Make API call to fetch servers
    const requestBody = {
        client_id: selectedClientId,
        test_mode: testMode
    };
    
    console.log('Making API request:', {
        url: '/api/fetch-servers',
        method: 'POST',
        body: requestBody,
        test_mode: testMode
    });
    
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
            // Show detailed success message
            const modePrefix = testMode ? '[TEST MODE] ' : '';
            const message = `${modePrefix}Servers fetched successfully! Created: ${data.data.created}, Updated: ${data.data.updated}, Removed: ${data.data.removed}, Duplicates removed: ${data.data.duplicates_removed}`;
            showAlert(message, 'success');
            
            // Reload client servers
            if (selectedClientId) {
                loadClientServers(selectedClientId);
            }
        } else {
            let errorMessage = data.message || 'Unknown error';
            
            // Add troubleshooting tips for common errors
            if (data.message && data.message.includes('404')) {
                errorMessage += '\n\n💡 Troubleshooting:\n• Check if the endpoint exists on the client server\n• Verify the domain is correct\n• Try using Test Mode first';
            } else if (data.message && data.message.includes('403')) {
                errorMessage += '\n\n💡 Troubleshooting:\n• This might be a CORS issue\n• Check server configuration\n• Try using Test Mode first';
            } else if (data.message && data.message.includes('Connection failed')) {
                errorMessage += '\n\n💡 Troubleshooting:\n• Check if client server is online\n• Verify domain/IP is accessible\n• Try using Test Mode first';
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

function fetchAllClientsServers() {
    // Get all client IDs from the DOM
    const clientItems = document.querySelectorAll('.client-item');
    const clientIds = Array.from(clientItems).map(item => parseInt(item.getAttribute('data-client-id')));
    
    if (clientIds.length === 0) {
        showAlert('No clients found to fetch servers from', 'warning');
        return;
    }
    
    // Show loading modal
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    loadingModal.show();
    
    // Update loading message
    document.getElementById('loading-message').textContent = `Fetching servers from ${clientIds.length} clients...`;
    
    let completedCount = 0;
    let successCount = 0;
    let errorCount = 0;
    const results = [];
    
    // Process each client sequentially to avoid overwhelming the system
    async function processNextClient(index) {
        if (index >= clientIds.length) {
            // All clients processed
            loadingModal.hide();
            
            const summaryMessage = `Completed fetching servers from ${clientIds.length} clients. Success: ${successCount}, Errors: ${errorCount}`;
            showAlert(summaryMessage, errorCount === 0 ? 'success' : 'warning');
            
            // Reload current client servers if one is selected
            if (selectedClientId) {
                loadClientServers(selectedClientId);
            }
            return;
        }
        
        const clientId = clientIds[index];
        const clientItem = document.querySelector(`[data-client-id="${clientId}"]`);
        const clientName = clientItem.querySelector('h6').textContent;
        
        // Update loading message
        document.getElementById('loading-message').textContent = `Fetching servers from ${clientName} (${index + 1}/${clientIds.length})...`;
        
        try {
            const response = await fetch('/api/fetch-servers', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ client_id: clientId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                successCount++;
                results.push({
                    client: clientName,
                    status: 'success',
                    message: data.message
                });
            } else {
                errorCount++;
                results.push({
                    client: clientName,
                    status: 'error',
                    message: data.message
                });
            }
        } catch (error) {
            errorCount++;
            results.push({
                client: clientName,
                status: 'error',
                message: 'Network error: ' + error.message
            });
        }
        
        completedCount++;
        
        // Process next client
        setTimeout(() => processNextClient(index + 1), 1000); // 1 second delay between requests
    }
    
    // Start processing
    processNextClient(0);
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
                // Reload servers
                if (selectedClientId) {
                    loadClientServers(selectedClientId);
                }
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

function debugConnection(clientId) {
    if (!selectedClient) {
        showAlert('No client selected for debugging', 'warning');
        return;
    }

    console.log('=== Debugging Connection ===');
    console.log('Client:', selectedClient);
    console.log('Domain:', selectedClient.domain);
    
    // Show debug info
    const debugInfo = `
        🔍 Connection Debug Info:
        
        Client ID: ${selectedClient.id}
        Client Name: ${selectedClient.name}
        Domain: ${selectedClient.domain}
        IP: ${selectedClient.ip}
        
        Expected API URL: ${selectedClient.domain}/block_actions.php?action=server
        
        💡 Troubleshooting Steps:
        1. Check if the domain is accessible in your browser
        2. Verify the endpoint exists: ${selectedClient.domain}/block_actions.php
        3. Check for CORS headers in browser dev tools
        4. Try using Test Mode to verify basic functionality
        5. Check Laravel logs for detailed error information
    `;
    
    showAlert(debugInfo, 'info');
    
    // Also try to ping the domain
    console.log('Attempting to ping domain...');
    const testUrl = `https://httpbin.org/get?url=${encodeURIComponent(selectedClient.domain)}`;
    
    fetch(testUrl)
        .then(response => response.json())
        .then(data => {
            console.log('HTTPBin test response:', data);
        })
        .catch(error => {
            console.error('HTTPBin test failed:', error);
        });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert after header
    const header = document.querySelector('.header-bar');
    header.parentNode.insertBefore(alertDiv, header.nextSibling);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
@endpush