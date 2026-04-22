<?php

namespace App\Console\Commands;

use App\Jobs\RunAutomationCycle;
use Illuminate\Console\Command;

class RunDailyAutomation extends Command
{
    protected $signature = 'automation:run-daily';

    protected $description = 'Run daily lifecycle automation and deliver reminder notifications';

    public function handle(): int
    {
        RunAutomationCycle::dispatch(now()->toDateString());
        $this->info('Automation cycle queued successfully.');

        return self::SUCCESS;
    }
}
