<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        
        // Run monitoring every 5 minutes
        $schedule->command('monitoring:run')->everyFiveMinutes();
        $schedule->command('lines:assign-free-vps')->dailyAt('01:00');
        $schedule->command('lines:unassign-old')->dailyAt('01:00');        
        // Alternative schedules you can use:
        // $schedule->command('monitoring:run')->everyMinute();           // Every minute
        // $schedule->command('monitoring:run')->everyTwoMinutes();      // Every 2 minutes
        // $schedule->command('monitoring:run')->everyTenMinutes();      // Every 10 minutes
        // $schedule->command('monitoring:run')->hourly();               // Every hour
        // $schedule->command('monitoring:run')->daily();                // Every day at midnight
        // $schedule->command('monitoring:run')->dailyAt('13:00');      // Every day at 1 PM
        // $schedule->command('monitoring:run')->weekly();              // Every week
        // $schedule->command('monitoring:run')->monthly();             // Every month
        // $schedule->command('monitoring:run')->cron('0 */2 * * *');  // Custom cron expression (every 2 hours)
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
