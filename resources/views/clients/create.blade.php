@extends('layout.app')

@section('title', 'Create New Client')

@section('header')
<div class="header-bar">
    <h4 class="mb-0">Create New Client</h4>
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
                <h5 class="mb-0">Create New Client</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('clients.store') }}" method="POST" id="create-client-form">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Client Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="domain" class="form-label">Domain</label>
                            <input type="text" class="form-control" id="domain" name="domain" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="ip" class="form-label">IP Address</label>
                            <input type="text" class="form-control" id="ip" name="ip" required>
                        </div>
                        <div class="col-md-6">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('clients.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Client</button>
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
    const form = document.getElementById('create-client-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic validation
        const name = document.getElementById('name').value;
        const domain = document.getElementById('domain').value;
        const ip = document.getElementById('ip').value;
        
        if (!name || !domain || !ip) {
            alert('Please fill in all required fields');
            return;
        }
        
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