<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Line;
use App\Models\Client;
use App\Models\Server;
use App\Models\Vps;
use Carbon\Carbon;
use App\Http\Controllers\VpsController;

class AssignLinesToFreeVPS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lines:assign-free-vps {--days=1 : Number of days after which to assign lines}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign lines to free VPS';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        $this->info("Looking for lines assigned before: {$cutoffDate->format('Y-m-d H:i:s')}");
        
        $unassignedLines = Line::where(function($query) use ($cutoffDate) {
            $query->whereNull('assigned_at')
                  ->orWhere('assigned_at', '<', $cutoffDate);
        })->get();
        
        $freeVps = Vps::whereNull('linename')->get();
        $this->info("Found {$unassignedLines->count()} unassigned lines");
        $this->info("Found {$freeVps->count()} free VPSs");
        
        if ($unassignedLines->isEmpty()) {
            $this->info('No unassigned lines found.');
            return 0;
        }
        
        if ($freeVps->isEmpty()) {
            $this->info('No free VPSs found.');
            return 0;
        }
        
        $vpsController = new VpsController();
        $assignedCount = 0;
        $minCount = min($unassignedLines->count(), $freeVps->count());
        for ($i = 0; $i < $minCount; $i++) {
            $line = $unassignedLines[$i];
            $vps = $freeVps[$i];
            
            try {
                $this->info("Assigning line '{$line->username}' to VPS '{$vps->name}' (IP: {$vps->ip})");
                
                $result = $vpsController->assignLineToVpsCore($line->id, $vps->id);
                $this->info('✓ ' . $result['message']);
                $assignedCount++;
                
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Line assignment failed', [
                    'line_id' => $line->id,
                    'vps_id' => $vps->id,
                    'error' => $e->getMessage()
                ]);
                
                $this->error("✗ Failed to assign line '{$line->username}' to VPS '{$vps->name}': " . $e->getMessage());
            }
        }
        
        $this->info("Successfully assigned {$assignedCount} lines to VPSs.");
        
        if ($unassignedLines->count() > $freeVps->count()) {
            $remaining = $unassignedLines->count() - $freeVps->count();
            $this->warn("{$remaining} lines remain unassigned due to insufficient free VPSs.");
        }
        
        return 0;
    }
}