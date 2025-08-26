<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Log;
use App\Models\Vps;
use App\Models\Client;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        $query = Log::with(['client', 'vps']);

        // Filter by client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Filter by VPS
        if ($request->filled('vps_id')) {
            $query->where('vps_id', $request->vps_id);
        }

        // Filter by uptime status
        if ($request->filled('uptime_status')) {
            $query->where('uptime_status', $request->uptime_status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('checked_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('checked_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('checked_at', 'desc')->paginate(50);
        $clients = Client::all();
        $vpsList = Vps::all();

        return view('logs.index', compact('logs', 'clients', 'vpsList'));
    }

    public function show(Log $log)
    {
        $log->load(['client', 'vps']);
        return view('logs.show', compact('log'));
    }

    public function destroy(Log $log)
    {
        $log->delete();
        return redirect()->route('logs.index')->with('status', 'Log deleted successfully.');
    }

    public function clearOldLogs()
    {
        $retentionDays = config('monitoring.log_retention_days', 30);
        $cutoffDate = now()->subDays($retentionDays);

        $deletedCount = Log::where('checked_at', '<', $cutoffDate)->delete();

        return redirect()->route('logs.index')->with('status', "Cleared {$deletedCount} old logs.");
    }
} 