<?php

namespace Tests\Feature;

use App\Models\Hierarchy;
use App\Models\SystemRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManualTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_open_manual_but_does_not_see_leader_or_admin_sections(): void
    {
        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
        ]);

        $this->actingAs($member)
            ->get(route('manual.index'))
            ->assertOk()
            ->assertSee('Choose a task')
            ->assertSee('Record today&#039;s reading', false)
            ->assertDontSee('Monitor your branch')
            ->assertDontSee('Manage hierarchy and resolve vacancies');
    }

    public function test_leader_can_open_leader_sections_but_not_admin_operations_sections(): void
    {
        $leader = User::factory()->create([
            'role' => User::ROLE_TEAM_LEADER,
        ]);

        $team = Hierarchy::create([
            'name' => 'Team Manual',
            'type' => 'team',
            'leader_id' => $leader->id,
        ]);

        $leader->update(['hierarchy_id' => $team->id]);

        $this->actingAs($leader)
            ->get(route('manual.index'))
            ->assertOk()
            ->assertSee('Monitor your branch')
            ->assertSee('Open member records and cycle history')
            ->assertDontSee('Manage hierarchy and resolve vacancies');
    }

    public function test_admin_surface_user_sees_admin_manual_sections(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_MEMBER,
        ]);

        $admin->systemRoles()->attach(
            SystemRole::query()->where('slug', 'operations_admin')->value('id')
        );

        $this->actingAs($admin)
            ->get(route('manual.index'))
            ->assertOk()
            ->assertSee('Manage hierarchy and resolve vacancies')
            ->assertSee('Manage users from the directory')
            ->assertSee('Move or merge groups');
    }

    public function test_user_can_open_a_task_guide_page(): void
    {
        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
        ]);

        $this->actingAs($member)
            ->get(route('manual.show', 'record-todays-reading'))
            ->assertOk()
            ->assertSee('Record today&#039;s reading', false)
            ->assertSee('Quick Links')
            ->assertSee('Open Progress');
    }
}
