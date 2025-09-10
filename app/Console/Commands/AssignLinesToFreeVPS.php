<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
        
        $this->info("Looking for VPS with lines assigned before: {$cutoffDate->format('Y-m-d H:i:s')}");
        $oldAssignments = Vps::whereNotNull('linename')
            ->whereNotNull('assigned_at')
            ->where('assigned_at', '<', $cutoffDate)
            ->get();
        
            foreach ($oldAssignments as $vps) {
            $this->info("Assigning line {$vps->linename} to VPS {$vps->id}");
            
            $vps->update([
                'linename' => null,
                'serverdomain' => null,
                'domains' => null,
                'assigned_at' => null
            ]);
        }
        return 0;
    }
}
