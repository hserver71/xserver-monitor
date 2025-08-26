@extends('layout.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <a href="{{ route('logs.index') }}" class="text-indigo-600 hover:text-indigo-900">
            ← Back to Logs
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h1 class="text-2xl font-bold text-gray-900">Log Details</h1>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Log ID</dt>
                            <dd class="text-sm text-gray-900">{{ $log->id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Checked At</dt>
                            <dd class="text-sm text-gray-900">{{ $log->checked_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Line ID</dt>
                            <dd class="text-sm text-gray-900">{{ $log->line_id ?: 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Line Name</dt>
                            <dd class="text-sm text-gray-900">{{ $log->line_name ?: 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="text-sm text-gray-900">
                                @if($log->uptime_status)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Online
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Offline
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Client Information -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Client Information</h3>
                    @if($log->client)
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Client Name</dt>
                            <dd class="text-sm text-gray-900">{{ $log->client->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Client Domain</dt>
                            <dd class="text-sm text-gray-900">{{ $log->client->domain }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Client IP</dt>
                            <dd class="text-sm text-gray-900">{{ $log->client->ip }}</dd>
                        </div>
                    </dl>
                    @else
                    <p class="text-sm text-gray-500">No client information available</p>
                    @endif
                </div>
            </div>

            <!-- VPS Information -->
            @if($log->vps)
            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">VPS Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">VPS Name</dt>
                        <dd class="text-sm text-gray-900">{{ $log->vps->name ?: "VPS #{$log->vps->id}" }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">VPS IP</dt>
                        <dd class="text-sm text-gray-900">{{ $log->vps->ip ?: 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Server Domain</dt>
                        <dd class="text-sm text-gray-900">{{ $log->vps->serverdomain ?: 'N/A' }}</dd>
                    </div>
                </div>
            </div>
            @endif

            <!-- Check Details -->
            @if($log->check_details)
            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Check Details</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <pre class="text-sm text-gray-900">{{ json_encode(json_decode($log->check_details), JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
            @endif

            <!-- Admin Notes -->
            @if($log->admin_notes)
            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Admin Notes</h3>
                <div class="bg-yellow-50 rounded-lg p-4">
                    <p class="text-sm text-gray-900">{{ $log->admin_notes }}</p>
                </div>
            </div>
            @endif

            <!-- Timestamps -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                    <div>
                        <dt class="font-medium">Created At</dt>
                        <dd>{{ $log->created_at->format('Y-m-d H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium">Updated At</dt>
                        <dd>{{ $log->updated_at->format('Y-m-d H:i:s') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-6 flex justify-between">
        <form action="{{ route('logs.destroy', $log) }}" method="POST" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" 
                    onclick="return confirm('Are you sure you want to delete this log?')">
                Delete Log
            </button>
        </form>
    </div>
</div>
@endsection 