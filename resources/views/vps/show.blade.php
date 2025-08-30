@extends('layout.app')

@section('title', 'VPS Details')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">VPS Details</h4>
    <div>
        <a href="{{ route('vps.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
        <a href="{{ route('vps.edit', $vps) }}" class="btn btn-sm btn-warning">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">VPS Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">VPS Name:</label>
                        <p class="form-control-plaintext">{{ $vps->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Server IP:</label>
                        <p class="form-control-plaintext"><code>{{ $vps->ip ?? 'N/A' }}</code></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Client:</label>
                        <p class="form-control-plaintext">{{ $vps->client->name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Server:</label>
                        <p class="form-control-plaintext">{{ $vps->server->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Line Name:</label>
                        <p class="form-control-plaintext">
                            @if($vps->linename)
                                <span class="badge bg-info">{{ $vps->linename }}</span>
                            @else
                                <span class="text-muted">Not set</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Server Domain:</label>
                        <p class="form-control-plaintext">
                            @if($vps->serverdomain)
                                <code>{{ $vps->serverdomain }}</code>
                            @else
                                <span class="text-muted">Not set</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Created:</label>
                        <p class="form-control-plaintext">{{ $vps->created_at->format('M d, Y H:i:s') }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Last Updated:</label>
                        <p class="form-control-plaintext">{{ $vps->updated_at->format('M d, Y H:i:s') }}</p>
                    </div>
                </div>
                
                @if($vps->domains)
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label fw-bold">Domains:</label>
                        <p class="form-control-plaintext">{{ $vps->domains }}</p>
                    </div>
                </div>
                @endif
                
                @if($vps->username)
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Username:</label>
                        <p class="form-control-plaintext">{{ $vps->username }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Password:</label>
                        <p class="form-control-plaintext">{{ $vps->password ? '••••••••' : 'N/A' }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="{{ route('vps.edit', $vps) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i> Edit VPS
                    </a>
                    <form action="{{ route('vps.destroy', $vps) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this VPS?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i> Delete VPS
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 