<?php

namespace Tests\Feature;

use App\Models\Hierarchy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_user_keeps_existing_reading_plan_enrolment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $plan = \App\Models\ReadingPlan::create([
            'name' => 'Directory Cohort',
            'type' => \App\Models\ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => \App\Models\ReadingPlan::STATUS_ACTIVE,
            'description' => 'Directory cohort description',
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'is_active' => true,
        ]);

        $user->readingPlans()->attach($plan->id, [
            'joined_date' => now()->subDays(3)->toDateString(),
            'current_day' => 4,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => null,
            'reading_plans' => [$plan->id],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('user_reading_plans', [
            'user_id' => $user->id,
            'reading_plan_id' => $plan->id,
            'current_day' => 4,
        ]);
    }

    public function test_admin_can_activate_an_inactive_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.activate', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'users.activated']);
    }

    public function test_admin_can_deactivate_an_active_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.deactivate', $user));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'users.deactivated']);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.deactivate', $admin));

        $response->assertSessionHas('error');
        $this->assertNotNull($admin->fresh()->email_verified_at);
    }

    public function test_admin_can_bulk_activate_users(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $users = User::factory()->count(3)->create(['email_verified_at' => null]);

        $response = $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.bulk-action'), [
                'action' => 'activate',
                'user_ids' => $users->pluck('id')->all(),
            ]);

        $response->assertSessionHas('success');
        $users->each(fn (User $user) => $this->assertNotNull($user->fresh()->email_verified_at));
    }

    public function test_admin_can_change_users_list_page_size(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        User::factory()->count(30)->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'per_page' => 25,
        ]));

        $response->assertOk();
        $response->assertViewHas('users', fn ($users) => $users->perPage() === 25);
        $response->assertViewHas('perPage', 25);
    }

    public function test_admin_directory_stays_paginated_with_large_user_counts(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        User::factory()->count(1005)->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'per_page' => 100,
        ]));

        $response->assertOk();
        $response->assertViewHas('users', fn ($users) => $users->perPage() === 100
            && $users->total() === 1006
            && $users->count() === 100);
        $response->assertViewHas('perPage', 100);
    }

    public function test_admin_can_bulk_promote_users_into_their_current_vacant_assignments(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $team = Hierarchy::create([
            'name' => 'Team Vacant',
            'type' => 'team',
            'leader_id' => null,
        ]);

        $candidate = User::factory()->create([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $team->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.bulk-action'), [
                'action' => 'promote_current_assignment',
                'user_ids' => [$candidate->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $candidate->id,
            'role' => User::ROLE_TEAM_LEADER,
            'hierarchy_id' => $team->id,
        ]);
        $this->assertDatabaseHas('hierarchies', [
            'id' => $team->id,
            'leader_id' => $candidate->id,
        ]);
    }

    public function test_admin_can_bulk_demote_leaders_from_directory_safely(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $batchLeader = User::factory()->create(['role' => User::ROLE_BATCH_LEADER]);
        $teamLeader = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);

        $batch = Hierarchy::create([
            'name' => 'Batch Bulk Demote',
            'type' => 'batch',
            'leader_id' => $batchLeader->id,
        ]);

        $team = Hierarchy::create([
            'name' => 'Team Landing',
            'type' => 'team',
            'leader_id' => $teamLeader->id,
            'parent_id' => $batch->id,
        ]);

        $batchLeader->update(['hierarchy_id' => $batch->id]);
        $teamLeader->update(['hierarchy_id' => $team->id]);

        $this->actingAs($admin)
            ->post(route('admin.users.bulk-action'), [
                'action' => 'demote_from_leadership',
                'user_ids' => [$batchLeader->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('hierarchies', [
            'id' => $batch->id,
            'leader_id' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $batchLeader->id,
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $team->id,
        ]);
    }

    public function test_balance_prefill_filters_directory_and_preserves_selected_target_teams(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $batch = Hierarchy::create([
            'name' => 'Batch Balance',
            'type' => 'batch',
            'leader_id' => null,
        ]);

        $teamHeavy = Hierarchy::create([
            'name' => 'Team Heavy',
            'type' => 'team',
            'leader_id' => null,
            'parent_id' => $batch->id,
        ]);

        $teamLight = Hierarchy::create([
            'name' => 'Team Light',
            'type' => 'team',
            'leader_id' => null,
            'parent_id' => $batch->id,
        ]);

        $heavyMember = User::factory()->create([
            'name' => 'Heavy Member',
            'hierarchy_id' => $teamHeavy->id,
        ]);

        User::factory()->create([
            'name' => 'Light Member',
            'hierarchy_id' => $teamLight->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'action' => 'distribute_evenly',
            'hierarchy_id' => $teamHeavy->id,
            'source_team_id' => $teamHeavy->id,
            'suggested_move_count' => 2,
            'target_team_ids' => [$teamHeavy->id, $teamLight->id],
        ]));

        $response->assertOk();
        $response->assertSee('Rebalance suggestion');
        $response->assertSee($heavyMember->name);
        $response->assertDontSee('Light Member');
        $response->assertViewHas('prefillTargetTeamIds', [$teamHeavy->id, $teamLight->id]);
    }
}
