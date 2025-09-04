<?php

namespace Tests\Feature\Auth;

use App\Models\Superadmin; // ⬅️ ganti User → Superadmin
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt as LivewireVolt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    public function test_superadmins_can_authenticate_using_the_login_screen(): void
    {
        $superadmin = Superadmin::factory()->create();

        $response = LivewireVolt::test('auth.login')
            ->set('email', $superadmin->email)
            ->set('password', 'password')
            ->call('login');

        $response
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($superadmin, 'web'); // ⬅️ pakai guard 'web'
    }

    public function test_superadmins_can_not_authenticate_with_invalid_password(): void
    {
        $superadmin = Superadmin::factory()->create();

        $response = LivewireVolt::test('auth.login')
            ->set('email', $superadmin->email)
            ->set('password', 'wrong-password')
            ->call('login');

        $response->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_superadmins_can_logout(): void
    {
        $superadmin = Superadmin::factory()->create();

        $response = $this->actingAs($superadmin, 'web')->post(route('logout'));

        $response->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
