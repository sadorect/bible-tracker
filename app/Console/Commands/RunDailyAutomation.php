<?php

namespace App\Console\Commands;

use App\Services\Automation\AutomationRunner;
use Illuminate\Console\Command;

class RunDailyAutomation extends Command
{
    protected $signature = 'automation:run-daily';

    protected $description = 'Run daily lifecycle automation and deliver reminder notifications';

    public function __construct(
        private readonly AutomationRunner $automationRunner,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $summary = $this->automationRunner->run();

        $this->info('Automation cycle completed.');
        $this->table(
            ['Metric', 'Count'],
            collect($summary)->map(fn ($count, $metric) => [str($metric)->replace('_', ' ')->headline()->toString(), $count])->all()
        );

        return self::SUCCESS;
    }
}
