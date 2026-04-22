<?php

namespace Tests\Feature;

use App\Models\BibleChapter;
use App\Models\ReadingPlan;
use App\Models\TrainingResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminReadingPlanConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_plan_with_custom_cadence(): void
    {
        $this->seedNewTestamentChapters();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.reading-plans.store'), [
            'name' => 'Flexible NT Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_RECRUITING,
            'description' => 'A custom plan.',
            'chapters_per_day' => 6,
            'streak_days' => 5,
            'break_days' => 2,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'enrollment_starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'enrollment_ends_at' => now()->addDays(10)->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect(route('admin.reading-plans.index'));

        $plan = ReadingPlan::where('name', 'Flexible NT Cohort')->firstOrFail();

        $this->assertSame(6, $plan->chapters_per_day);
        $this->assertSame(5, $plan->streak_days);
        $this->assertSame(2, $plan->break_days);
        $this->assertSame(ReadingPlan::STATUS_RECRUITING, $plan->lifecycle_status);
        $this->assertSame(60, $plan->dailyReadings()->count());
        $this->assertSame(16, $plan->dailyReadings()->where('is_break_day', true)->count());
    }

    public function test_default_live_plan_limits_block_a_second_new_testament_live_plan(): void
    {
        $this->seedNewTestamentChapters();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        ReadingPlan::create([
            'name' => 'Primary NT Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_RECRUITING,
            'description' => 'Current NT cohort',
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now()->addDays(5),
            'end_date' => null,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addDays(7),
            'is_active' => true,
        ]);

        $response = $this->from(route('admin.reading-plans.create'))
            ->actingAs($admin)
            ->post(route('admin.reading-plans.store'), [
                'name' => 'Second NT Cohort',
                'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
                'lifecycle_status' => ReadingPlan::STATUS_RECRUITING,
                'description' => 'Should be blocked by default',
                'chapters_per_day' => 6,
                'streak_days' => 5,
                'break_days' => 1,
                'start_date' => now()->addDays(10)->format('Y-m-d'),
            ]);

        $response->assertRedirect(route('admin.reading-plans.create'));
        $response->assertSessionHasErrors('lifecycle_status');
    }

    public function test_admin_can_relax_live_plan_limits_and_create_another_nt_plan(): void
    {
        $this->seedNewTestamentChapters();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        ReadingPlan::create([
            'name' => 'Primary NT Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_RECRUITING,
            'description' => 'Current NT cohort',
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now()->addDays(5),
            'end_date' => null,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addDays(7),
            'is_active' => true,
        ]);

        $this->actingAs($admin)->put(route('admin.reading-plans.settings.update'), [
            'max_live_new_testament' => 2,
            'max_live_old_testament' => 1,
            'max_live_total' => 3,
        ])->assertRedirect(route('admin.reading-plans.index'));

        $response = $this->actingAs($admin)->post(route('admin.reading-plans.store'), [
            'name' => 'Second NT Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_RECRUITING,
            'description' => 'Allowed after settings update',
            'chapters_per_day' => 6,
            'streak_days' => 5,
            'break_days' => 1,
            'start_date' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('admin.reading-plans.index'));
        $this->assertDatabaseHas('reading_plans', [
            'name' => 'Second NT Cohort',
            'lifecycle_status' => ReadingPlan::STATUS_RECRUITING,
        ]);
    }

    public function test_admin_can_attach_both_video_and_pdf_to_one_training_resource(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $plan = ReadingPlan::create([
            'name' => 'Training Resource Plan',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'description' => 'Testing resources',
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now(),
            'end_date' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.reading-plans.training-resources.store', $plan),
            [
                'title' => 'Orientation Pack',
                'resource_url' => 'https://www.youtube.com/watch?v=abc123xyz00',
                'resource_file' => UploadedFile::fake()->create('orientation.pdf', 120, 'application/pdf'),
                'description' => 'Watch and read.',
                'sort_order' => 1,
            ]
        );

        $response->assertRedirect(route('admin.reading-plans.edit', $plan));

        $resource = TrainingResource::where('reading_plan_id', $plan->id)->firstOrFail();

        $this->assertSame(TrainingResource::TYPE_COMBINED, $resource->resource_type);
        $this->assertNotNull($resource->resource_url);
        $this->assertNotNull($resource->resource_path);
        Storage::disk('public')->assertExists($resource->resource_path);
    }

    private function seedNewTestamentChapters(): void
    {
        $rows = [];

        for ($chapter = 1; $chapter <= 260; $chapter++) {
            $rows[] = [
                'book_name' => 'Test Book',
                'chapter_number' => $chapter,
                'day_number' => 1,
                'testament' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        BibleChapter::insert($rows);
    }
}
