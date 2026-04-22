<?php

namespace Tests\Feature;

use App\Models\Hierarchy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHierarchyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_hierarchy_and_assign_matching_leader(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $squadLeader = User::factory()->create(['role' => User::ROLE_SQUAD_LEADER]);

        $response = $this->actingAs($admin)->post(route('admin.hierarchies.store'), [
            'name' => 'Squad North',
            'type' => 'squad',
            'leader_id' => $squadLeader->id,
            'parent_id' => null,
        ]);

        $response->assertRedirect(route('admin.hierarchies.index'));
        $this->assertDatabaseHas('hierarchies', [
            'name' => 'Squad North',
            'type' => 'squad',
            'leader_id' => $squadLeader->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $squadLeader->id,
            'hierarchy_id' => Hierarchy::where('name', 'Squad North')->value('id'),
        ]);
    }

    public function test_admin_cannot_assign_wrong_leader_role_to_hierarchy(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $batchLeader = User::factory()->create(['role' => User::ROLE_BATCH_LEADER]);

        $response = $this->actingAs($admin)->from(route('admin.hierarchies.index'))->post(route('admin.hierarchies.store'), [
            'name' => 'Team Delta',
            'type' => 'team',
            'leader_id' => $batchLeader->id,
            'parent_id' => null,
        ]);

        $response->assertRedirect(route('admin.hierarchies.index'));
        $response->assertSessionHasErrors('leader_id');
        $this->assertDatabaseMissing('hierarchies', [
            'name' => 'Team Delta',
        ]);
    }

    public function test_admin_can_update_hierarchy_leader_and_parent(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $squadLeader = User::factory()->create(['role' => User::ROLE_SQUAD_LEADER]);
        $platoonLeaderOne = User::factory()->create(['role' => User::ROLE_PLATOON_LEADER]);
        $platoonLeaderTwo = User::factory()->create(['role' => User::ROLE_PLATOON_LEADER]);

        $squad = Hierarchy::create([
            'name' => 'Squad One',
            'type' => 'squad',
            'leader_id' => $squadLeader->id,
        ]);

        $platoon = Hierarchy::create([
            'name' => 'Platoon Alpha',
            'type' => 'platoon',
            'leader_id' => $platoonLeaderOne->id,
        ]);

        $platoonLeaderOne->update(['hierarchy_id' => $platoon->id]);

        $response = $this->actingAs($admin)->put(route('admin.hierarchies.update', $platoon), [
            'name' => 'Platoon Alpha',
            'type' => 'platoon',
            'leader_id' => $platoonLeaderTwo->id,
            'parent_id' => $squad->id,
        ]);

        $response->assertRedirect(route('admin.hierarchies.index'));
        $this->assertDatabaseHas('hierarchies', [
            'id' => $platoon->id,
            'leader_id' => $platoonLeaderTwo->id,
            'parent_id' => $squad->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $platoonLeaderTwo->id,
            'hierarchy_id' => $platoon->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $platoonLeaderOne->id,
            'hierarchy_id' => null,
        ]);
    }

    public function test_admin_can_bulk_assign_members_to_a_team(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $teamLeader = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);
        $memberOne = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $memberTwo = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $team = Hierarchy::create([
            'name' => 'Team Omega',
            'type' => 'team',
            'leader_id' => $teamLeader->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.bulk-action'), [
            'action' => 'assign_hierarchy',
            'user_ids' => [$memberOne->id, $memberTwo->id],
            'hierarchy_id' => $team->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $memberOne->id,
            'hierarchy_id' => $team->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $memberTwo->id,
            'hierarchy_id' => $team->id,
        ]);
    }

    public function test_admin_cannot_bulk_assign_members_to_non_team_hierarchy(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $batchLeader = User::factory()->create(['role' => User::ROLE_BATCH_LEADER]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $batch = Hierarchy::create([
            'name' => 'Batch East',
            'type' => 'batch',
            'leader_id' => $batchLeader->id,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.bulk-action'), [
                'action' => 'assign_hierarchy',
                'user_ids' => [$member->id],
                'hierarchy_id' => $batch->id,
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHasErrors('hierarchy_id');
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'hierarchy_id' => null,
        ]);
    }

    public function test_admin_can_distribute_members_evenly_across_selected_teams(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $batchLeader = User::factory()->create(['role' => User::ROLE_BATCH_LEADER]);
        $teamLeaderOne = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);
        $teamLeaderTwo = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);

        $batch = Hierarchy::create([
            'name' => 'Batch Central',
            'type' => 'batch',
            'leader_id' => $batchLeader->id,
        ]);

        $teamOne = Hierarchy::create([
            'name' => 'Team One',
            'type' => 'team',
            'leader_id' => $teamLeaderOne->id,
            'parent_id' => $batch->id,
        ]);

        $teamTwo = Hierarchy::create([
            'name' => 'Team Two',
            'type' => 'team',
            'leader_id' => $teamLeaderTwo->id,
            'parent_id' => $batch->id,
        ]);

        $members = User::factory()->count(4)->create(['role' => User::ROLE_MEMBER]);

        $response = $this->actingAs($admin)->post(route('admin.users.bulk-action'), [
            'action' => 'distribute_evenly',
            'user_ids' => $members->pluck('id')->all(),
            'target_team_ids' => [$teamOne->id, $teamTwo->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $teamOneCount = User::query()->where('hierarchy_id', $teamOne->id)->count();
        $teamTwoCount = User::query()->where('hierarchy_id', $teamTwo->id)->count();

        $this->assertSame(2, $teamOneCount);
        $this->assertSame(2, $teamTwoCount);
    }

    public function test_admin_can_promote_member_into_vacant_hierarchy_leadership(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $team = Hierarchy::create([
            'name' => 'Team Vacant',
            'type' => 'team',
            'leader_id' => null,
        ]);

        $member->update(['hierarchy_id' => $team->id]);

        $response = $this->actingAs($admin)->post(route('admin.hierarchies.promote-leader', $team), [
            'promote_user_id' => $member->id,
        ]);

        $response->assertRedirect(route('admin.hierarchies.index'));
        $this->assertDatabaseHas('hierarchies', [
            'id' => $team->id,
            'leader_id' => $member->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'role' => User::ROLE_TEAM_LEADER,
            'hierarchy_id' => $team->id,
        ]);
    }

    public function test_admin_can_demote_non_team_leader_into_descendant_team(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $batchLeader = User::factory()->create(['role' => User::ROLE_BATCH_LEADER]);
        $teamLeader = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);

        $batch = Hierarchy::create([
            'name' => 'Batch South',
            'type' => 'batch',
            'leader_id' => $batchLeader->id,
        ]);

        $team = Hierarchy::create([
            'name' => 'Team South',
            'type' => 'team',
            'leader_id' => $teamLeader->id,
            'parent_id' => $batch->id,
        ]);

        $batchLeader->update(['hierarchy_id' => $batch->id]);

        $response = $this->actingAs($admin)->post(route('admin.hierarchies.demote-leader', $batch), [
            'demote_target_team_id' => $team->id,
        ]);

        $response->assertRedirect(route('admin.hierarchies.index'));
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

    public function test_hierarchy_index_shows_team_balance_insight_for_uneven_batch(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $batchLeader = User::factory()->create(['role' => User::ROLE_BATCH_LEADER]);
        $teamLeaderOne = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);
        $teamLeaderTwo = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);

        $batch = Hierarchy::create([
            'name' => 'Batch Insight',
            'type' => 'batch',
            'leader_id' => $batchLeader->id,
        ]);

        $teamOne = Hierarchy::create([
            'name' => 'Team Heavy',
            'type' => 'team',
            'leader_id' => $teamLeaderOne->id,
            'parent_id' => $batch->id,
        ]);

        $teamTwo = Hierarchy::create([
            'name' => 'Team Light',
            'type' => 'team',
            'leader_id' => $teamLeaderTwo->id,
            'parent_id' => $batch->id,
        ]);

        User::factory()->count(5)->create([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $teamOne->id,
        ]);

        User::factory()->count(1)->create([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $teamTwo->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.hierarchies.index'))
            ->assertOk()
            ->assertSee('Sibling teams that may need a rebalance')
            ->assertSee('Batch Insight')
            ->assertSee('Spread 4');
    }
}
