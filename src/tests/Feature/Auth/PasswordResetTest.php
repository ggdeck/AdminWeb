<?php

namespace Tests\Feature\Auth;

use App\Models\Superadmin;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $superadmin = Superadmin::factory()->create();

        Volt::test('auth.forgot-password')
            ->set('email', $superadmin->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($superadmin, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $superadmin = Superadmin::factory()->create();

        Volt::test('auth.forgot-password')
            ->set('email', $superadmin->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($superadmin, ResetPassword::class, function ($notification) {
            $response = $this->get(route('password.reset', $notification->token));

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $superadmin = Superadmin::factory()->create();

        Volt::test('auth.forgot-password')
            ->set('email', $superadmin->email)
            ->call('sendPasswordResetLink');

        Notification::assertSentTo($superadmin, ResetPassword::class, function ($notification) use ($superadmin) {
            $response = Volt::test('auth.reset-password', ['token' => $notification->token])
                ->set('email', $superadmin->email)
                ->set('password', 'password')
                ->set('password_confirmation', 'password')
                ->call('resetPassword');

            $response
                ->assertHasNoErrors()
                ->assertRedirect(route('login', absolute: false));

            return true;
        });
    }
}
