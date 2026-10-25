<?php

namespace App\Console\Commands;

use App\Models\SiteVisit;
use Illuminate\Console\Command;

class PruneSiteVisits extends Command
{
    protected $signature = 'site-visits:prune {--days= : Retain visits newer than this many days}';

    protected $description = 'Delete site visit analytics rows older than the retention window.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('analytics.site_visit_retention_days'));

        if ($days < 1) {
            $this->error('Retention must be at least 1 day.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $deleted = 0;

        // Chunked so a long-neglected table does not lock rows in one huge delete.
        do {
            $batch = SiteVisit::where('created_at', '<', $cutoff)->limit(1000)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        $this->info("Pruned {$deleted} site visit(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
