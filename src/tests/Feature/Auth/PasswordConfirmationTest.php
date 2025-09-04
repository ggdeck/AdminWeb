<?php

namespace Tests\Feature\Auth;

use App\Models\Superadmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_can_be_rendered(): void
    {
        $superadmin = Superadmin::factory()->create();

        $response = $this->actingAs($superadmin)->get(route('password.confirm'));

        $response->assertStatus(200);
    }

    public function test_password_can_be_confirmed(): void
    {
        $superadmin = Superadmin::factory()->create([
            'password' => bcrypt('password'), // pastikan sama kayak yang dites
        ]);

        $this->actingAs($superadmin);

        $response = Volt::test('auth.confirm-password')
            ->set('password', 'password')
            ->call('confirmPassword');

        $response
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_password_is_not_confirmed_with_invalid_password(): void
    {
        $superadmin = Superadmin::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($superadmin);

        $response = Volt::test('auth.confirm-password')
            ->set('password', 'wrong-password')
            ->call('confirmPassword');

        $response->assertHasErrors(['password']);
    }
}
