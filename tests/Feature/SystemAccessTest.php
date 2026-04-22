<?php

namespace Tests\Feature;

use App\Models\SystemRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_system_access_role_can_enter_admin_panel(): void
    {
        $leader = User::factory()->create([
            'role' => User::ROLE_TEAM_LEADER,
        ]);

        $leader->systemRoles()->attach(
            SystemRole::query()->where('slug', 'operations_admin')->value('id')
        );

        $response = $this->actingAs($leader)->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_user_without_admin_access_permission_is_redirected_away_from_admin_panel(): void
    {
        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
        ]);

        $response = $this->actingAs($member)->get(route('admin.users.index'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_reports_admin_can_view_reports_but_cannot_manage_users(): void
    {
        $leader = User::factory()->create([
            'role' => User::ROLE_BATCH_LEADER,
        ]);

        $leader->systemRoles()->attach(
            SystemRole::query()->where('slug', 'reports_admin')->value('id')
        );

        $this->actingAs($leader)
            ->get(route('admin.progress.index'))
            ->assertOk();

        $this->actingAs($leader)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_assign_system_access_roles_when_updating_user(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_MEMBER,
        ]);

        $roleId = SystemRole::query()->where('slug', 'plans_admin')->value('id');

        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'hierarchy_id' => null,
            'message_delivery_preference' => '',
            'message_delivery_preference_locked' => false,
            'reading_plans' => [],
            'system_role_ids' => [$roleId],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertTrue($user->fresh()->systemRoles->contains('slug', 'plans_admin'));
    }

    public function test_only_authorized_users_can_manage_system_roles(): void
    {
        $operationsAdmin = User::factory()->create([
            'role' => User::ROLE_MEMBER,
        ]);

        $operationsAdmin->systemRoles()->attach(
            SystemRole::query()->where('slug', 'operations_admin')->value('id')
        );

        $this->actingAs($operationsAdmin)
            ->get(route('admin.system-roles.index'))
            ->assertForbidden();
    }
}
