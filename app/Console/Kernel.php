<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:send-work-request-update')->everyMinute();
        $schedule->command('app:counter-offer-update')->everyMinute();
        $schedule->command('payments:update-status')->daily();
        $schedule->command('points:add-monthly')->monthly();
        $schedule->command('subscriptions:update-status')->daily();
        $schedule->command('workorder:check-schedule')->everyFiveMinutes();
        $schedule->command('app:check-work-order-confirmation')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
