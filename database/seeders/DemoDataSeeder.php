<?php

namespace Database\Seeders;

use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use App\Models\TrainingCompletion;
use App\Models\TrainingResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activePlan = $this->makePlan(
            name: 'Current New Testament Cohort',
            startDate: Carbon::today()->subDays(4),
            description: 'Demo active cohort for leader visibility and member testing.'
        );

        $upcomingPlan = $this->makePlan(
            name: 'Upcoming New Testament Cohort',
            startDate: Carbon::today()->addDay(),
            description: 'Demo upcoming cohort showing training completed but reading not yet open.'
        );

        $activeResources = [
            $this->upsertTrainingResource($activePlan, 'Orientation Video', 'https://www.youtube.com/watch?v=abc123xyz00', 1),
            $this->upsertTrainingResource($activePlan, 'Daily Reading Guide', 'https://www.youtube.com/watch?v=abc123xyz01', 2),
        ];

        $upcomingResources = [
            $this->upsertTrainingResource($upcomingPlan, 'Upcoming Cohort Orientation', 'https://www.youtube.com/watch?v=abc123xyz02', 1),
        ];

        $activePlan->refresh();
        $activePlan->syncScheduleDates();
        $upcomingPlan->refresh();
        $upcomingPlan->syncScheduleDates();

        $activeUsers = [
            'team.alpha.leader@example.com',
            'team.bravo.leader@example.com',
            'alpha.ontrack@example.com',
            'alpha.catchup@example.com',
            'alpha.ahead@example.com',
            'alpha.training@example.com',
            'bravo.ontrack@example.com',
            'bravo.training@example.com',
            'bravo.ahead@example.com',
        ];

        foreach ($activeUsers as $email) {
            $this->enroll(User::where('email', $email)->firstOrFail(), $activePlan, 3);
        }

        $this->enroll(User::where('email', 'alpha.awaiting@example.com')->firstOrFail(), $upcomingPlan, 1);

        foreach ([
            'team.alpha.leader@example.com',
            'team.bravo.leader@example.com',
            'alpha.ontrack@example.com',
            'alpha.catchup@example.com',
            'alpha.ahead@example.com',
            'bravo.ontrack@example.com',
            'bravo.ahead@example.com',
        ] as $email) {
            $this->completeTraining(User::where('email', $email)->firstOrFail(), $activeResources);
        }

        $this->completeTraining(
            User::where('email', 'alpha.awaiting@example.com')->firstOrFail(),
            $upcomingResources
        );

        $this->markDays(User::where('email', 'team.alpha.leader@example.com')->firstOrFail(), $activePlan, [1, 2, 3]);
        $this->markDays(User::where('email', 'team.bravo.leader@example.com')->firstOrFail(), $activePlan, [1, 2, 3]);
        $this->markDays(User::where('email', 'alpha.ontrack@example.com')->firstOrFail(), $activePlan, [1, 2, 3]);
        $this->markDays(User::where('email', 'alpha.catchup@example.com')->firstOrFail(), $activePlan, [1]);
        $this->markDays(User::where('email', 'alpha.ahead@example.com')->firstOrFail(), $activePlan, [1, 2, 3, 4]);
        $this->markDays(User::where('email', 'bravo.ontrack@example.com')->firstOrFail(), $activePlan, [1, 2, 3]);
        $this->markDays(User::where('email', 'bravo.ahead@example.com')->firstOrFail(), $activePlan, [1, 2, 3, 4]);

        $this->command?->info('Demo plans, training resources, enrollments, and progress seeded.');
    }

    private function makePlan(string $name, Carbon $startDate, string $description): ReadingPlan
    {
        $defaults = ReadingPlan::defaultsFor(ReadingPlan::TYPE_NEW_TESTAMENT);

        $plan = ReadingPlan::updateOrCreate(
            ['name' => $name],
            [
                'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
                'description' => $description,
                'chapters_per_day' => $defaults['chapters_per_day'],
                'streak_days' => $defaults['streak_days'],
                'break_days' => $defaults['break_days'],
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addDays(
                    max(
                        $defaults['total_chapters'] > 0
                            ? (int) ceil($defaults['total_chapters'] / $defaults['chapters_per_day'])
                                + (intdiv(max((int) ceil($defaults['total_chapters'] / $defaults['chapters_per_day']) - 1, 0), $defaults['streak_days']) * $defaults['break_days'])
                            : 1,
                        1
                    ) - 1
                ),
                'is_active' => true,
                'additional_info' => 'Seeded demo plan for local testing.',
            ]
        );

        if ($plan->dailyReadings()->count() === 0) {
            Artisan::call('reading:generate', ['plan_id' => $plan->id]);
        }

        return $plan->fresh();
    }

    private function upsertTrainingResource(ReadingPlan $plan, string $title, string $url, int $sortOrder): TrainingResource
    {
        return TrainingResource::updateOrCreate(
            [
                'reading_plan_id' => $plan->id,
                'title' => $title,
            ],
            [
                'resource_type' => TrainingResource::TYPE_YOUTUBE,
                'resource_url' => $url,
                'resource_path' => null,
                'description' => $title.' for seeded demo data.',
                'sort_order' => $sortOrder,
            ]
        );
    }

    private function enroll(User $user, ReadingPlan $plan, int $currentDay): void
    {
        $user->readingPlans()->syncWithoutDetaching([
            $plan->id => [
                'joined_date' => Carbon::today()->toDateString(),
                'current_day' => $currentDay,
                'current_streak' => 0,
                'completion_rate' => 0,
                'is_active' => true,
            ],
        ]);

        $user->readingPlans()->updateExistingPivot($plan->id, [
            'joined_date' => Carbon::today()->toDateString(),
            'current_day' => $currentDay,
            'current_streak' => 0,
            'completion_rate' => 0,
            'is_active' => true,
        ]);
    }

    private function completeTraining(User $user, array $resources): void
    {
        foreach ($resources as $index => $resource) {
            TrainingCompletion::firstOrCreate(
                [
                    'training_resource_id' => $resource->id,
                    'user_id' => $user->id,
                ],
                [
                    'completed_at' => Carbon::today()->subDays(max(count($resources) - $index, 1)),
                ]
            );
        }
    }

    private function markDays(User $user, ReadingPlan $plan, array $dayNumbers): void
    {
        $dailyReadings = $plan->dailyReadings()
            ->whereIn('day_number', $dayNumbers)
            ->where('is_break_day', false)
            ->get()
            ->keyBy('day_number');

        foreach ($dayNumbers as $dayNumber) {
            $reading = $dailyReadings->get($dayNumber);

            if (! $reading) {
                continue;
            }

            ReadingProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'daily_reading_id' => $reading->id,
                ],
                [
                    'reading_plan_id' => $plan->id,
                    'completed_date' => Carbon::today(),
                ]
            );
        }
    }
}
