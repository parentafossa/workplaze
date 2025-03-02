<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        Log::info('Schedule method in Kernel.php is being executed at: ' . now()->toDateTimeString());

        $schedule->command('attendance:update')->everyFiveMinutes();
        $schedule->call(function () {
            \Log::info('Scheduler is running!');
        })->everyMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}

/*
$schedule->command('approvals:send-reminders --threshold=24')
    ->everyFourHours()
    ->between('8:00', '18:00')
    ->weekdays();

// Daily report generation (end of day)
$schedule->command('approvals:generate-reports --period=daily')
    ->dailyAt('18:00')
    ->weekdays();

// Weekly report generation (Friday evening)
$schedule->command('approvals:generate-reports --period=weekly')
    ->weeklyOn(5, '17:00');

// Monthly report generation (last day of month)
$schedule->command('approvals:generate-reports --period=monthly')
    ->monthlyOn(1, '01:00');

// Clean up old reports (keep last 3 months)
$schedule->command('approvals:cleanup-reports --older-than=90')
    ->daily();
    */