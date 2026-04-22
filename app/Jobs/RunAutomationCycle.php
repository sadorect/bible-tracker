<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Automation\AutomationRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class RunAutomationCycle implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $runDate,
        public readonly bool $manual = false,
        public readonly ?int $actorId = null,
    ) {
    }

    public function handle(AutomationRunner $automationRunner): void
    {
        $automationRunner->run(
            Carbon::parse($this->runDate),
            $this->manual,
            $this->actorId ? User::query()->find($this->actorId) : null,
        );
    }
}
