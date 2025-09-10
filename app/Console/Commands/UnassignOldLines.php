<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vps;
use App\Models\Line;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UnassignOldLines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lines:unassign-old {--days=1 : Number of days after which to unassign lines}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unassign lines from VPS that have been assigned for more than specified days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Looking for VPS with lines assigned before: {$cutoffDate->format('Y-m-d H:i:s')}");
        
        // Find VPS with lines assigned more than the specified days ago
        $oldAssignments = Vps::whereNotNull('linename')
            ->whereNotNull('assigned_at')
            ->where('assigned_at', '<', $cutoffDate)
            ->get();
        
        if ($oldAssignments->isEmpty()) {
            $this->info('No VPS found with lines assigned for more than ' . $days . ' day(s).');
            return 0;
        }
        
        $this->info('Found ' . $oldAssignments->count() . ' VPS with old line assignments.');
        
        $unassignedCount = 0;
        
        foreach ($oldAssignments as $vps) {
            try {
                $this->line("Unassigning line '{$vps->linename}' from VPS '{$vps->name}' (assigned: {$vps->assigned_at})");
                $line = Line::where('username', $vps->linename)->where('client_id', $vps->client_id)->first();
                if ($line) {
                    $line->update([
                        'assigned_at' => null
                    ]);
                }
                $vps->update([
                    'linename' => null,
                    'serverdomain' => null,
                    'domains' => null,
                    'assigned_at' => null
                ]);
                $unassignedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to unassign line from VPS '{$vps->name}': " . $e->getMessage());
                Log::error('Failed to unassign line by cron job', [
                    'vps_id' => $vps->id,
                    'vps_name' => $vps->name,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info("Successfully unassigned {$unassignedCount} lines from VPS.");
        
        return 0;
    }
}
