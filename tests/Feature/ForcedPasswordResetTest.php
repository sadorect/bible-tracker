<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcedPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function flaggedUser(): User
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $user->forceFill(['force_password_reset' => true])->save();

        return $user;
    }

    public function test_a_flagged_user_is_redirected_off_every_page(): void
    {
        $user = $this->flaggedUser();

        foreach (['/dashboard', '/profile', '/messages', '/reading-plans'] as $path) {
            $this->actingAs($user)->get($path)->assertRedirect(route('password.forced'));
        }
    }

    public function test_a_flagged_user_can_still_reach_the_reset_screen(): void
    {
        $this->actingAs($this->flaggedUser())
            ->get(route('password.forced'))
            ->assertOk()
            ->assertSee('Choose a new password');
    }

    public function test_an_unflagged_user_is_not_redirected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertOk();
        $this->actingAs($user)->get(route('password.forced'))->assertRedirect(route('dashboard'));
    }

    public function test_setting_a_new_password_clears_the_flag(): void
    {
        $user = $this->flaggedUser();

        $this->actingAs($user)->put(route('password.forced.update'), [
            'password' => 'a-Fresh-Passphrase-1',
            'password_confirmation' => 'a-Fresh-Passphrase-1',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertFalse($user->mustResetPassword());
        $this->assertTrue(Hash::check('a-Fresh-Passphrase-1', $user->password));

        $this->actingAs($user)->get('/profile')->assertOk();
    }

    public function test_reusing_the_same_password_is_rejected(): void
    {
        $user = $this->flaggedUser();

        $this->actingAs($user)->from(route('password.forced'))
            ->put(route('password.forced.update'), [
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->refresh()->mustResetPassword());
    }

    public function test_a_flagged_user_can_log_out(): void
    {
        $this->actingAs($this->flaggedUser())->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_the_gate_cannot_be_bypassed_via_the_normal_password_endpoint(): void
    {
        $user = $this->flaggedUser();

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'another-Good-Passphrase-2',
            'password_confirmation' => 'another-Good-Passphrase-2',
        ])->assertRedirect(route('password.forced'));

        // Still flagged, and still on the old password.
        $this->assertTrue($user->refresh()->mustResetPassword());
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
