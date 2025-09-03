@extends('layout.app')

@section('title', 'Edit Client')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">Edit Client</h4>
    <div>
        <a href="{{ route('clients.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Client</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('clients.update', $client) }}" id="edit-client-form">
                    @csrf
                    @method('PUT')

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Client Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $client->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="ip" class="form-label">IP Address</label>
                            <input type="text" name="ip" id="ip" class="form-control" value="{{ old('ip', $client->ip) }}" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('clients.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-client-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        const name = document.getElementById('name').value;
        const domain = document.getElementById('domain').value;
        const ip = document.getElementById('ip').value;
        
        if (!name || !ip) {
            alert('Please fill in all required fields');
            return;
        }
        
        // Domain validation (only if domain is provided)
        // if (domain) {
        //     const domainPattern = /^[a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]?\.([a-zA-Z]{2,}|[a-zA-Z]{2,}\.[a-zA-Z]{2,})$/;
        //     if (!domainPattern.test(domain)) {
        //         alert('Please enter a valid domain name');
        //         return;
        //     }
        // }
        
        // IP validation
        const ipPattern = /^(?!0)(?!.*\.$)((1?\d?\d|25[0-5]|2[0-4]\d)(\.|$)){4}$/;
        if (!ipPattern.test(ip)) {
            alert('Please enter a valid IP address');
            return;
        }
        
        // If validation passes, submit the form
        this.submit();
    });
});
</script>
@endsection 