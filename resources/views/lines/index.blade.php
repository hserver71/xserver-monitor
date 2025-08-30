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
                                                    <div>
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
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-mouse-pointer fa-2x mb-2"></i>
                                            <p>Select a line to view VPS</p>
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
</div>

<!-- Loading Spinner Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Loading...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    console.log('Lines page loaded successfully'); // Debug log
    let currentClientId = null;
    let currentLineId = null;

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
        
        showLoading(true);
        
        $.ajax({
            url: '/api/lines/client/' + currentClientId + '/fetch-store',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    alert(response.message);
                    // Refresh the lines display
                    fetchStoredLines(currentClientId);
                } else {
                    alert('Failed to fetch lines: ' + response.message);
                }
                showLoading(false);
            },
            error: function(xhr, status, error) {
                hideLoading();
                alert('Error fetching lines: ' + error);
            },
            complete: function() {
                // Ensure loading is always hidden
                hideLoading();
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
        clearVpsSection();
        
        // Show fetch button
        $('#fetchLinesBtn').show();
        
        // Show loading
        showLoading(true);
        
        // Fetch stored lines for this client
        fetchStoredLines(clientId);
        showLoading(false);
    });

    // Line click handler (delegated)
    $(document).on('click', '.line-item', function(e) {
        e.preventDefault();
        
        const lineId = $(this).data('line-id');
        
        // Update active state
        $('.line-item').removeClass('active');
        $(this).addClass('active');
        
        // Clear VPS section
        clearVpsSection();
        
        // Show loading
        showLoading(true);
        
        // Fetch VPS for this line
        fetchLineVps(lineId, currentClientId);
        showLoading(false);
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
                hideLoading();
                if (response.success) {
                    displayLines(response.lines);
                } else {
                    showError('Failed to fetch stored lines: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', status, error); // Debug log
                console.log('Response:', xhr.responseText); // Debug log
                hideLoading();
                showError('Error fetching stored lines. Please try again.');
            },
            complete: function() {
                // Ensure loading is always hidden
                hideLoading();
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
                hideLoading();
                if (response.success) {
                    displayLines(response.lines);
                } else {
                    showError('Failed to fetch lines: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', status, error); // Debug log
                console.log('Response:', xhr.responseText); // Debug log
                hideLoading();
                showError('Error fetching lines. Please try again.');
            },
            complete: function() {
                // Ensure loading is always hidden
                hideLoading();
            }
        });
    }

    function fetchLineVps(lineId, clientId) {
        currentLineId = lineId;
        
        $.ajax({
            url: '/api/lines/line/' + lineId + '/client/' + clientId + '/vps',
            method: 'GET',
            success: function(response) {
                hideLoading();
                if (response.success) {
                    displayVps(response.vps);
                } else {
                    showError('Failed to fetch VPS: ' + response.message);
                }
            },
            error: function(xhr) {
                hideLoading();
                showError('Error fetching VPS. Please try again.');
            },
            complete: function() {
                // Ensure loading is always hidden
                hideLoading();
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

    function displayVps(vpsInstances) {
        const vpsList = $('#vpsList');
        
        if (vpsInstances && vpsInstances.length > 0) {
            let html = '';
            vpsInstances.forEach(function(vps) {
                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">VPS ${vps.id}</h6>
                                <small class="text-muted">
                                    Server: ${vps.server_id} | Client: ${vps.client_id}
                                </small>
                            </div>
                            <span class="badge bg-primary">Active</span>
                        </div>
                    </div>
                `;
            });
            vpsList.html(html);
        } else {
            vpsList.html(`
                <div class="text-center text-muted py-4">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>No VPS found for this line</p>
                </div>
            `);
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

    function showLoading(isShow=true) {
        if(isShow){
            $('#loadingModal').modal('show');
        }else {
            hideLoading();
        }
    }

    function hideLoading() {
        console.log('Hiding loading spinner'); // Debug log
        $('#loadingModal').modal('hide');
    }

    function showError(message) {
        // You can implement a better error display method
        alert(message);
    }
});
</script>
@endpush 