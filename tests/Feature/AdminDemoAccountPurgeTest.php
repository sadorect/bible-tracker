<?php

namespace Tests\Feature;

use App\Models\Hierarchy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDemoAccountPurgeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'ops@biblereading.test',
        ]);
    }

    public function test_the_review_page_lists_only_reserved_domain_accounts(): void
    {
        $demo = User::factory()->create(['email' => 'clan@example.com']);
        $real = User::factory()->create(['email' => 'grace@gmail.com']);

        $this->actingAs($this->admin())
            ->get(route('admin.users.demo-accounts'))
            ->assertOk()
            ->assertSee('clan@example.com')
            ->assertDontSee('grace@gmail.com');
    }

    public function test_purging_removes_demo_accounts_and_keeps_real_ones(): void
    {
        $admin = $this->admin();
        User::factory()->create(['email' => 'clan@example.com']);
        User::factory()->create(['email' => 'member0001@example.com']);
        User::factory()->create(['email' => 'also@example.org']);
        $real = User::factory()->create(['email' => 'grace@gmail.com']);

        $this->actingAs($admin)
            ->delete(route('admin.users.demo-accounts.purge'), ['confirm' => '1'])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['email' => 'clan@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'member0001@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'also@example.org']);
        $this->assertDatabaseHas('users', ['email' => 'grace@gmail.com']);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'users.demo_accounts_purged']);
    }

    public function test_purging_requires_confirmation(): void
    {
        User::factory()->create(['email' => 'clan@example.com']);

        $this->actingAs($this->admin())
            ->from(route('admin.users.demo-accounts'))
            ->delete(route('admin.users.demo-accounts.purge'), [])
            ->assertSessionHasErrors('confirm');

        $this->assertDatabaseHas('users', ['email' => 'clan@example.com']);
    }

    public function test_purging_releases_hierarchies_led_by_a_demo_account(): void
    {
        $leader = User::factory()->create(['email' => 'team.alpha.leader@example.com']);
        $hierarchy = Hierarchy::create([
            'name' => 'Team Alpha',
            'type' => 'team',
            'leader_id' => $leader->id,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.users.demo-accounts.purge'), ['confirm' => '1']);

        $this->assertDatabaseMissing('users', ['id' => $leader->id]);
        $this->assertNull($hierarchy->refresh()->leader_id);
    }

    public function test_a_non_admin_cannot_reach_the_purge_tools(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)->get(route('admin.users.demo-accounts'))->assertRedirect();
        $this->actingAs($member)
            ->delete(route('admin.users.demo-accounts.purge'), ['confirm' => '1'])
            ->assertRedirect();
    }

    public function test_an_admin_can_require_a_password_reset_for_a_user(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.require-password-reset', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($target->refresh()->mustResetPassword());
        $this->assertDatabaseHas('audit_logs', ['event' => 'users.password_reset_required']);
    }
}
