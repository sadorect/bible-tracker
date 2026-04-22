<?php

namespace Tests\Feature;

use App\Models\DailyReading;
use App\Models\Hierarchy;
use App\Models\ReadingPlan;
use App\Models\TrainingResource;
use App\Models\User;
use App\Notifications\AutomationAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationRunTest extends TestCase
{
    use RefreshDatabase;

    public function test_automation_command_transitions_plan_lifecycle_states(): void
    {
        $recruitingPlan = ReadingPlan::create([
            'name' => 'Recruiting Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_RECRUITING,
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => today()->subDay(),
            'end_date' => today()->addDays(10),
            'is_active' => true,
        ]);

        $activePlan = ReadingPlan::create([
            'name' => 'Closing Cohort',
            'type' => ReadingPlan::TYPE_OLD_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_ACTIVE,
            'chapters_per_day' => 8,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => today()->subDays(40),
            'end_date' => today()->subDay(),
            'is_active' => true,
        ]);

        $this->artisan('automation:run-daily')->assertSuccessful();

        $this->assertSame(ReadingPlan::STATUS_ACTIVE, $recruitingPlan->fresh()->lifecycle_status);
        $this->assertSame(ReadingPlan::STATUS_CLOSED, $activePlan->fresh()->lifecycle_status);
    }

    public function test_automation_command_creates_member_reminders_and_leader_digest(): void
    {
        $leader = User::factory()->create([
            'role' => User::ROLE_TEAM_LEADER,
            'message_delivery_preference' => User::MESSAGE_DELIVERY_INBOX,
        ]);

        $team = Hierarchy::create([
            'name' => 'Team Alpha',
            'type' => 'team',
            'leader_id' => $leader->id,
        ]);

        $leader->update(['hierarchy_id' => $team->id]);

        $readingMember = User::factory()->create([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $team->id,
            'message_delivery_preference' => User::MESSAGE_DELIVERY_INBOX,
        ]);

        $trainingMember = User::factory()->create([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $team->id,
            'message_delivery_preference' => User::MESSAGE_DELIVERY_INBOX,
        ]);

        $readingPlan = ReadingPlan::create([
            'name' => 'Reading Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_ACTIVE,
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => today()->subDay(),
            'end_date' => today()->addDays(20),
            'is_active' => true,
        ]);

        DailyReading::create([
            'reading_plan_id' => $readingPlan->id,
            'day_number' => 1,
            'book_start' => 'Matthew',
            'chapter_start' => 1,
            'book_end' => 'Matthew',
            'chapter_end' => 1,
            'is_break_day' => false,
        ]);
        DailyReading::create([
            'reading_plan_id' => $readingPlan->id,
            'day_number' => 2,
            'book_start' => 'Matthew',
            'chapter_start' => 2,
            'book_end' => 'Matthew',
            'chapter_end' => 2,
            'is_break_day' => false,
        ]);

        $trainingPlan = ReadingPlan::create([
            'name' => 'Training Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_ACTIVE,
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => today(),
            'end_date' => today()->addDays(20),
            'is_active' => true,
        ]);

        $trainingPlan->trainingResources()->create([
            'title' => 'Orientation',
            'resource_type' => TrainingResource::TYPE_YOUTUBE,
            'resource_url' => 'https://www.youtube.com/watch?v=abc123xyz00',
            'sort_order' => 1,
        ]);
        $trainingPlan->syncScheduleDates();

        $readingMember->readingPlans()->attach($readingPlan->id, [
            'joined_date' => today()->subDay(),
            'current_participation_id' => null,
            'current_day' => 2,
            'current_streak' => 0,
            'completion_rate' => 0,
            'is_active' => true,
        ]);

        $trainingMember->readingPlans()->attach($trainingPlan->id, [
            'joined_date' => today(),
            'current_participation_id' => null,
            'current_day' => 1,
            'current_streak' => 0,
            'completion_rate' => 0,
            'is_active' => true,
        ]);

        $this->artisan('automation:run-daily')->assertSuccessful();

        $this->assertTrue($readingMember->fresh()->notifications->contains(function ($notification) {
            return $notification->data['category'] === 'reading_reminder';
        }));

        $this->assertTrue($trainingMember->fresh()->notifications->contains(function ($notification) {
            return $notification->data['category'] === 'training_reminder';
        }));

        $this->assertTrue($leader->fresh()->notifications->contains(function ($notification) {
            return $notification->data['category'] === 'leader_digest';
        }));
    }

    public function test_notification_center_allows_marking_alerts_as_read(): void
    {
        $user = User::factory()->create([
            'message_delivery_preference' => User::MESSAGE_DELIVERY_INBOX,
        ]);

        $user->notify(new AutomationAlertNotification(
            'Reminder',
            'Please record your reading for today.',
            'manual-test-key',
            route('dashboard'),
            'Open dashboard',
        ));

        $notification = $user->fresh()->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Reminder');

        $this->actingAs($user)
            ->patch(route('notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_automation_command_sends_admin_vacancy_alerts(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'message_delivery_preference' => User::MESSAGE_DELIVERY_INBOX,
        ]);

        $vacantTeam = Hierarchy::create([
            'name' => 'Team Vacant',
            'type' => 'team',
            'leader_id' => null,
        ]);

        $this->artisan('automation:run-daily')->assertSuccessful();

        $notification = $admin->fresh()->notifications->firstWhere('data.category', 'vacancy_alert');

        $this->assertNotNull($notification);
        $this->assertSame(route('admin.hierarchies.resolve-vacancy', $vacantTeam), $notification->data['action_url']);
        $this->assertSame('Team Vacant', data_get($notification->data, 'vacancies.0.path'));
    }

    public function test_notification_preferences_can_disable_reminders_but_admin_vacancy_alerts_still_arrive(): void
    {
        $member = User::factory()->create([
            'message_delivery_preference' => User::MESSAGE_DELIVERY_INBOX,
            'notification_preferences' => [
                'reminders' => User::NOTIFICATION_DELIVERY_OFF,
            ],
        ]);

        $member->notify(new AutomationAlertNotification(
            'Reading reminder',
            'Today is ready.',
            'reading-pref-test',
            route('dashboard'),
            'Open dashboard',
            'emerald',
            'reading_reminder',
        ));

        $this->assertCount(0, $member->fresh()->notifications);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'message_delivery_preference' => User::MESSAGE_DELIVERY_INBOX,
            'notification_preferences' => [
                'vacancy_alert' => User::NOTIFICATION_DELIVERY_OFF,
            ],
        ]);

        $admin->notify(new AutomationAlertNotification(
            'Vacancy alert',
            'A team has no leader.',
            'vacancy-pref-test',
            route('admin.hierarchies.index'),
            'Open hierarchies',
            'amber',
            'vacancy_alert',
        ));

        $this->assertCount(1, $admin->fresh()->notifications);
    }
}
