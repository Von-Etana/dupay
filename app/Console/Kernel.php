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
        // Reconcile any transactions stuck in 'pending' against the provider's
        // requery endpoint every 5 minutes. Requires the underlying cron job:
        //   * * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
        $schedule->command('tx:reconcile --minutes=5')
                 ->everyFiveMinutes()
                 ->withoutOverlapping();
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