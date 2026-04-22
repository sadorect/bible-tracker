<?php

use App\Models\ReadingPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('reading_plans', 'lifecycle_status')
            || ! Schema::hasColumn('reading_plans', 'enrollment_starts_at')
            || ! Schema::hasColumn('reading_plans', 'enrollment_ends_at')) {
            Schema::table('reading_plans', function (Blueprint $table) {
                if (! Schema::hasColumn('reading_plans', 'lifecycle_status')) {
                    $table->string('lifecycle_status', 20)
                        ->default(ReadingPlan::STATUS_DRAFT)
                        ->after('type');
                }

                if (! Schema::hasColumn('reading_plans', 'enrollment_starts_at')) {
                    $table->timestamp('enrollment_starts_at')
                        ->nullable()
                        ->after('end_date');
                }

                if (! Schema::hasColumn('reading_plans', 'enrollment_ends_at')) {
                    $table->timestamp('enrollment_ends_at')
                        ->nullable()
                        ->after('enrollment_starts_at');
                }
            });
        }

        ReadingPlan::query()
            ->get()
            ->each(function (ReadingPlan $plan) {
                $status = $plan->is_active
                    ? ($plan->start_date && $plan->start_date->isFuture()
                        ? ReadingPlan::STATUS_RECRUITING
                        : ReadingPlan::STATUS_ACTIVE)
                    : ReadingPlan::STATUS_DRAFT;

                $plan->forceFill([
                    'lifecycle_status' => $status,
                    'enrollment_starts_at' => $plan->start_date
                        ? Carbon::parse($plan->start_date)->startOfDay()
                        : null,
                    'enrollment_ends_at' => $plan->end_date
                        ? Carbon::parse($plan->end_date)->endOfDay()
                        : null,
                    'is_active' => in_array($status, ReadingPlan::liveStatuses(), true),
                ])->saveQuietly();
            });
    }

    public function down(): void
    {
        Schema::table('reading_plans', function (Blueprint $table) {
            $columns = collect([
                'lifecycle_status',
                'enrollment_starts_at',
                'enrollment_ends_at',
            ])->filter(fn (string $column) => Schema::hasColumn('reading_plans', $column))
                ->values()
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
