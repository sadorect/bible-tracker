<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserDirectoryTest extends TestCase
{
    use RefreshDatabase;

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
}
