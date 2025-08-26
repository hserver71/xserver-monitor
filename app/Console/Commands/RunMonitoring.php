<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use Illuminate\Console\Command;

class RunMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the monitoring system for all VPS instances';

    /**
     * Execute the console command.
     */
    public function handle(MonitoringService $monitoringService)
    {
        $this->info('Starting monitoring job...');
        
        try {
            $monitoringService->runMonitoring();
            $this->info('Monitoring job completed successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Monitoring job failed: ' . $e->getMessage());
            return 1;
        }
    }
} 