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
                                                    <button class="badge bg-primary border-none" onclick="vpsAction({{ $vpsItem->id }}, {{ $vpsItem->linename == null ? false : true }})"  >{{ $vpsItem->linename == null ? 'Assign' : 'Unassign' }}</button>
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
@endsection

@push('scripts')
<script>
// Global variables and functions
let currentClientId = null;
let currentLineId = null;

// Global function for VPS action
function vpsAction(vpsId, isAssigned) {
    if(currentLineId == null) {
        alert('Please select a line first');
        return;
    }
    if (isAssigned) {
        unassignLineFromVps(vpsId);
    } else {
        assignLineToVps(vpsId);
    }
}

function unassignLineFromVps(vpsId) {
    $.ajax({
        url: '/api/unassign-line',
        method: 'POST',
        data: {
            vps_id: vpsId
        },
        success: function(response) {
            console.log('Unassign line response:', response);
        }
    });
}

function assignLineToVps(vpsId) {
    console.log('Assigning line to VPS:', vpsId);
    let lineId = currentLineId;
    if (lineId == null) {
        alert('Please select a line first');
        return;
    }
    $.ajax({
        url: '/api/assign-line',
        method: 'POST',
        data: {
            line_id: lineId,
            vps_id: vpsId
        },
        success: function(response) {
            console.log('Assign line response:', response);
        }
    });
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
        
        $.ajax({
            url: '/api/lines/client/' + currentClientId + '/fetch-store',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    // Refresh the lines display
                    fetchStoredLines(currentClientId);
                } else {
                    alert('Failed to fetch lines: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error fetching lines: ' + error);
            }
        });
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

    function showError(message) {
        // You can implement a better error display method
        alert(message);
    }
});
</script>
@endpush
