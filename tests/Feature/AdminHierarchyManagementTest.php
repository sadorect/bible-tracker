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
}
