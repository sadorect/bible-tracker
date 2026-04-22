<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Scheduling is registered from bootstrap/app.php via withSchedule().
     * This class remains available for Artisan command discovery.
     */
    protected function schedule(Schedule $schedule): void
    {
        //
    }
}
