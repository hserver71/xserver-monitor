@extends('layout.app')

@section('title', 'Servers')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">Servers</h4>
    <div>
        <a href="{{ route('servers.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Add Server
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Server List</h5>
    </div>
    <div class="card-body">
        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($servers->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>IP Address</th>
                            <th>Domain</th>
                            <th>Client</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($servers as $server)
                            <tr>
                                <td>{{ $server[id] }}</td>
                                <td>{{ $server[server_name] }}</td>
                                <td>{{ $server[server_ip] }}</td>
                                <td>{{ $server[domain_name] ?? 'N/A' }}</td>
                                <td>{{ $server[uptime]->format('M d, Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('servers.show', $server) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('servers.edit', $server) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('servers.destroy', $server) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this server?')">
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
                <p class="text-muted">No servers found.</p>
                <a href="{{ route('servers.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Your First Server
                </a>
            </div>
        @endif
    </div>
</div>
@endsection 