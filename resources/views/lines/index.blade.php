@extends('layout.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Lines Management</h3>
                    <button id="testBtn" class="btn btn-sm btn-secondary">Test API</button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Clients Section -->
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-users"></i> Clients
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush" id="clientsList">
                                        @foreach($clients as $client)
                                            <a class="list-group-item list-group-item-action client-item" 
                                               data-client-id="{{ $client->id }}" 
                                               data-client-domain="{{ $client->domain }}">
                                                <div class="d-flex justify-content-between align-items-center" style="cursor: pointer;width: 100%;">
                                                    <div style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
                                                        <h6 class="mb-1">{{ $client->name }}</h6>

                                                        <small class="text-muted">{{ $client->ip }} - {{ $client->domain }}</small>
                                                    </div>
                                                    <i class="fas fa-chevron-right"></i>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lines Section -->
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-network-wired"></i> Current Lines
                                    </h5>
                                    <button id="fetchLinesBtn" class="btn btn-sm btn-primary" style="display: none;">
                                        <i class="fas fa-sync-alt"></i> Fetch & Store
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush" id="linesList">
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                                            <p>Select a client to view lines</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- VPS Section -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-server"></i> VPS
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush" id="vpsList">
                                        @foreach($vps as $vpsItem)
                                            <div class="d-flex justify-content-between align-items-center" style="cursor: pointer;width: 100%; padding: 10px;">
                                                <div style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
                                                    <h6><strong>  VPS  </strong></h6>
                                                    <h6><strong>id {{ $vpsItem->id }} </strong></h6> 
                                                    <h6><strong>{{ $vpsItem->name}}</strong></h6>
                                                    <h6><strong>{{ $vpsItem->linename}}</strong></h6>
                                                    <h6><small class="text-muted">Server: {{ $vpsItem->server_id }} | Client: {{ $vpsItem->client_id }}</small></h6>
                                                    <button 
                                                        class="badge border-none {{ $vpsItem->linename == null ? 'bg-primary' : 'bg-warning' }}" 
                                                        onclick="vpsAction({{ $vpsItem->id }}, {{ $vpsItem->linename == null ? false : true }})"  
                                                    >
                                                    {{ $vpsItem->linename == null ? 'Assign' : 'Unassign' }}</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mb-2" id="loadingModalTitle">Processing...</h5>
                <p class="text-muted mb-0" id="loadingModalMessage">Please wait while we process your request.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Global variables and functions
let currentClientId = null;
let currentLineId = null;

// Global function for VPS action
function vpsAction(vpsId, isAssigned) {
    console.log('VPS action:', isAssigned);
    if (isAssigned) {
        unassignLineFromVps(vpsId);
    } else {
        assignLineToVps(vpsId);
    }
}

function unassignLineFromVps(vpsId) {
    // Show loading modal
    showLoadingModal('Unassigning Line', 'Please wait while we unassign the line from VPS...');
    
    $.ajax({
        url: '/api/unassign-line',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            vps_id: vpsId
        },
        success: function(response) {
            console.log('Unassign line response:', response);
            hideLoadingModal();
            
            if (response.success) {
                showSuccessMessage(response.message || 'Line unassigned successfully');
                refreshVpsList();
            } else {
                showError(response.message || 'Failed to unassign line');
            }
        },
        error: function(xhr, status, error) {
            console.log('Unassign error:', status, error);
            hideLoadingModal();
            showError('Error unassigning line: ' + error);
        }
    });
    hideLoadingModal();
}

function assignLineToVps(vpsId) {
    console.log('Assigning line to VPS:', vpsId);
    let lineId = currentLineId;
    if (lineId == null) {
        alert('Please select a line first');
        return;
    }
    
    // Show loading modal
    showLoadingModal('Assigning Line', 'Please wait while we assign the line to VPS and configure nginx...');
    
    $.ajax({
        url: '/api/assign-line',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            line_id: lineId,
            vps_id: vpsId
        },
        success: function(response) {
            console.log('Assign line response:', response);
            hideLoadingModal();
            
            if (response.success) {
                showSuccessMessage(response.message || 'Line assigned successfully');
                refreshVpsList();
            } else {
                showError(response.message || 'Failed to assign line');
            }
        },
        error: function(xhr, status, error) {
            console.log('Assign error:', status, error);
            hideLoadingModal();
            showError('Error assigning line: ' + error);
        }
    });
}

// Helper functions (moved to global scope)
function showSuccessMessage(message) {
    alert('Success: ' + message);
}

function showError(message) {
    alert('Error: ' + message);
}

function showLoadingModal(title = 'Processing...', message = 'Please wait while we process your request.') {
    $('#loadingModalTitle').text(title);
    $('#loadingModalMessage').text(message);
    $('#loadingModal').modal('show');
}

function hideLoadingModal() {
    console.log('Hiding loading modal');
    $('#loadingModal').modal('hide');
}

function refreshVpsList() {
    if (!currentClientId) {
        console.log('No current client selected, skipping VPS refresh');
        return;
    }
    
    console.log('Refreshing VPS list for client:', currentClientId);
    
    $.ajax({
        url: '/api/vps',
        method: 'GET',
        success: function(response) {
            console.log('VPS refresh response:', response);
            if (response.success && response.vps) {
                displayVpsList(response.vps);
            }
        },
        error: function(xhr, status, error) {
            console.log('Error refreshing VPS list:', status, error);
        }
    });
    hideLoadingModal();
}

function displayVpsList(vpsList) {
    const vpsContainer = $('#vpsList');
    
    if (vpsList && vpsList.length > 0) {
        let html = '';
        vpsList.forEach(function(vpsItem) {
            const isAssigned = vpsItem.linename !== null && vpsItem.linename !== '';
            const buttonText = isAssigned ? 'Unassign' : 'Assign';
            const buttonClass = isAssigned ? 'bg-warning' : 'bg-primary';
            
            html += `
                <div class="d-flex justify-content-between align-items-center" style="cursor: pointer;width: 100%; padding: 10px;">
                    <div style="width: 100%; display: flex; justify-content: space-between; align-items: center;">
                        <h6><strong>VPS</strong></h6>
                        <h6><strong>id ${vpsItem.id}</strong></h6> 
                        <h6><strong>${vpsItem.name}</strong></h6>
                        <h6><strong>${vpsItem.linename || ''}</strong></h6>
                        <h6><small class="text-muted">Server: ${vpsItem.server_id} | Client: ${vpsItem.client_id}</small></h6>
                        <button class="badge ${buttonClass} border-none" onclick="vpsAction(${vpsItem.id}, ${isAssigned})">${buttonText}</button>
                    </div>
                </div>
            `;
        });
        vpsContainer.html(html);
    } else {
        vpsContainer.html(`
            <div class="text-center text-muted py-4">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <p>No VPS found for this client</p>
            </div>
        `);
    }
}

$(document).ready(function() {
    console.log('Lines page loaded successfully'); // Debug log

    // Test button handler
    $('#testBtn').on('click', function() {
        console.log('Test button clicked');
        $.ajax({
            url: '/api/lines/test',
            method: 'GET',
            success: function(response) {
                console.log('Test response:', response);
                alert('Test successful: ' + response.message);
            },
            error: function(xhr, status, error) {
                console.log('Test error:', status, error);
                alert('Test failed: ' + error);
            }
        });
    });

    // Fetch lines button handler
    $('#fetchLinesBtn').on('click', function() {
        if (!currentClientId) {
            alert('Please select a client first');
            return;
        }
        
        showLoadingModal('Fetching Lines', 'Please wait while we fetch and store lines from the API...');
        
        $.ajax({
            url: '/api/lines/client/' + currentClientId + '/fetch-store',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                hideLoadingModal();
                
                if (response.success) {
                    alert(response.message);
                    // Refresh the lines display
                    fetchStoredLines(currentClientId);
                } else {
                    alert('Failed to fetch lines: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                hideLoadingModal();
                alert('Error fetching lines: ' + error);
            }
        });
        hideLoadingModal();
    });

    // Client click handler
    $('.client-item').on('click', function(e) {
        e.preventDefault();
        
        const clientId = $(this).data('client-id');
        const clientDomain = $(this).data('client-domain');
        
        console.log('Client clicked:', clientId, clientDomain); // Debug log
        
        // Update active state
        $('.client-item').removeClass('active');
        $(this).addClass('active');
        
        // Clear lines and VPS sections
        clearLinesSection();
        // clearVpsSection();
        
        // Show fetch button
        $('#fetchLinesBtn').show();
        
        // Fetch stored lines for this client
        fetchStoredLines(clientId);
    });

    // Line click handler (delegated)
    $(document).on('click', '.line-item', function(e) {
        e.preventDefault();
        
        const lineId = $(this).data('line-id');
        currentLineId = lineId; // Update current line ID
        
        // Update active state
        $('.line-item').removeClass('active');
        $(this).addClass('active');
    });

    function fetchStoredLines(clientId) {
        currentClientId = clientId;
        
        const url = '/api/lines/client/' + clientId + '/stored-lines';
        console.log('Fetching stored lines from:', url); // Debug log
        
        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                console.log('Stored lines response:', response); // Debug log
                if (response.success) {
                    displayLines(response.lines);
                } else {
                    showError('Failed to fetch stored lines: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', status, error); // Debug log
                console.log('Response:', xhr.responseText); // Debug log
                showError('Error fetching stored lines. Please try again.');
            }
        });
    }

    function fetchClientLines(clientId, clientDomain) {
        currentClientId = clientId;
        
        const url = '/api/lines/client/' + clientId + '/lines';
        console.log('Fetching lines from:', url); // Debug log
        
        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                console.log('Lines response:', response); // Debug log
                if (response.success) {
                    displayLines(response.lines);
                } else {
                    showError('Failed to fetch lines: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', status, error); // Debug log
                console.log('Response:', xhr.responseText); // Debug log
                showError('Error fetching lines. Please try again.');
            }
        });
    }

    function displayLines(lines) {
        console.log('Displaying lines:', lines); // Debug log
        const linesList = $('#linesList');
        
        if (lines && lines.length > 0) {
            console.log('Lines found, displaying them'); // Debug log
            let html = '';
            lines.forEach(function(line) {
                const statusClass = getStatusClass(line.status);
                html += `
                    <a href="#" class="list-group-item list-group-item-action line-item" data-line-id="${line.id}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${line.username}</h6>
                                <small class="text-muted">Status: ${line.status}</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge ${statusClass} me-2">${line.status}</span>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </a>
                `;
            });
            linesList.html(html);
        } else {
            console.log('No lines found, showing empty state'); // Debug log
            linesList.html(`
                <div class="text-center text-muted py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>No lines found for this client</p>
                    <small>Click "Fetch & Store" to get lines from API</small>
                </div>
            `);
        }
    }

    function getStatusClass(status) {
        switch(status?.toLowerCase()) {
            case 'active':
            case 'online':
                return 'bg-success';
            case 'inactive':
            case 'offline':
                return 'bg-danger';
            case 'pending':
                return 'bg-warning';
            default:
                return 'bg-secondary';
        }
    }

    function clearLinesSection() {
        $('#linesList').html(`
            <div class="text-center text-muted py-4">
                <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                <p>Select a client to view lines</p>
            </div>
        `);
        $('#fetchLinesBtn').hide();
    }

    function clearVpsSection() {
        $('#vpsList').html(`
            <div class="text-center text-muted py-4">
                <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                <p>Select a line to view VPS</p>
            </div>
        `);
    }
});
</script>
@endpush
