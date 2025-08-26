@extends('layout.app')

@section('title', 'VPS Instances')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">VPS Instances</h4>
    <div>
        <a href="{{ route('vps.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Add VPS
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">VPS List</h5>
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
                            <th>Client</th>
                            <th>Server</th>
                            <th>Line ID</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vps as $vpsInstance)
                            <tr>
                                <td>{{ $vpsInstance->id }}</td>
                                <td>{{ $vpsInstance->client->name ?? 'N/A' }}</td>
                                <td>{{ $vpsInstance->server->name ?? 'N/A' }}</td>
                                <td>{{ $vpsInstance->line_id ?? 'N/A' }}</td>
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
@endsection