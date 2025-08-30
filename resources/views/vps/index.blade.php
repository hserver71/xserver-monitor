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
                                        <a href="{{ route('vps.show', $vpsInstance) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('vps.edit', $vpsInstance) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('vps.destroy', $vpsInstance) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this VPS?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
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

<!-- Lines Management Section -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Lines Management</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="client_filter" class="form-label">Select Client to View Lines</label>
                <select class="form-select" id="client_filter" name="client_filter">
                    <option value="">All Clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }} ({{ $client->ip }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="lines-container">
            <div class="text-center py-4">
                <p class="text-muted">Select a client to view and manage lines</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const clientFilter = document.getElementById('client_filter');
    const linesContainer = document.getElementById('lines-container');

    clientFilter.addEventListener('change', function() {
        const clientId = this.value;
        if (clientId) {
            loadLinesForClient(clientId);
        } else {
            linesContainer.innerHTML = '<div class="text-center py-4"><p class="text-muted">Select a client to view and manage lines</p></div>';
        }
    });

    function loadLinesForClient(clientId) {
        // Show loading
        linesContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        // Fetch lines for the selected client
        fetch(`/api/client/${clientId}/lines`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayLines(data.lines, data.vps);
                } else {
                    linesContainer.innerHTML = '<div class="alert alert-danger">Error loading lines: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                linesContainer.innerHTML = '<div class="alert alert-danger">Error loading lines: ' + error.message + '</div>';
            });
    }

    function displayLines(lines, vps) {
        if (lines.length === 0) {
            linesContainer.innerHTML = '<div class="text-center py-4"><p class="text-muted">No lines found for this client</p></div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-striped table-hover">';
        html += '<thead><tr><th>Line Username</th><th>Status</th><th>Assigned VPS</th><th>Actions</th></tr></thead><tbody>';
        
        lines.forEach(line => {
            const assignedVps = vps.find(v => v.linename === line.username);
            html += '<tr>';
            html += '<td><strong>' + line.username + '</strong></td>';
            html += '<td><span class="badge bg-' + (line.status === 'active' ? 'success' : 'secondary') + '">' + line.status + '</span></td>';
            html += '<td>';
            if (assignedVps) {
                html += '<span class="badge bg-info">' + assignedVps.name + '</span>';
            } else {
                html += '<span class="text-muted">Not assigned</span>';
            }
            html += '</td>';
            html += '<td>';
            if (!assignedVps) {
                html += '<select class="form-select form-select-sm assign-vps-select" data-line-id="' + line.id + '" style="width: 150px;">';
                html += '<option value="">Select VPS</option>';
                vps.forEach(v => {
                    if (!v.linename) {
                        html += '<option value="' + v.id + '">' + v.name + '</option>';
                    }
                });
                html += '</select>';
            } else {
                html += '<button class="btn btn-sm btn-warning unassign-line" data-line-id="' + line.id + '" data-vps-id="' + assignedVps.id + '">Unassign</button>';
            }
            html += '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table></div>';
        linesContainer.innerHTML = html;

        // Add event listeners for VPS assignment
        document.querySelectorAll('.assign-vps-select').forEach(select => {
            select.addEventListener('change', function() {
                const lineId = this.dataset.lineId;
                const vpsId = this.value;
                if (vpsId) {
                    assignLineToVps(lineId, vpsId);
                }
            });
        });

        // Add event listeners for unassigning
        document.querySelectorAll('.unassign-line').forEach(button => {
            button.addEventListener('click', function() {
                const lineId = this.dataset.lineId;
                const vpsId = this.dataset.vpsId;
                unassignLineFromVps(lineId, vpsId);
            });
        });
    }

    function assignLineToVps(lineId, vpsId) {
        // Show loading state
        const select = document.querySelector(`[data-line-id="${lineId}"]`);
        const originalValue = select.value;
        select.disabled = true;
        
        fetch('/api/assign-line-to-vps', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                line_id: lineId,
                vps_id: vpsId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the lines for the current client
                loadLinesForClient(clientFilter.value);
            } else {
                alert('Error assigning line: ' + data.message);
                select.value = originalValue;
                select.disabled = false;
            }
        })
        .catch(error => {
            alert('Error assigning line: ' + error.message);
            select.value = originalValue;
            select.disabled = false;
        });
    }

    function unassignLineFromVps(lineId, vpsId) {
        if (!confirm('Are you sure you want to unassign this line from the VPS?')) {
            return;
        }

        fetch('/api/unassign-line-from-vps', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                line_id: lineId,
                vps_id: vpsId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the lines for the current client
                loadLinesForClient(clientFilter.value);
            } else {
                alert('Error unassigning line: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error unassigning line: ' + error.message);
        });
    }
});
</script>
@endsection