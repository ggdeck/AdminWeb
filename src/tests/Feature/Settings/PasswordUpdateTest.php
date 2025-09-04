<?php

namespace Tests\Feature\Settings;

use App\Models\Superadmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $superadmin = Superadmin::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($superadmin);

        $response = Volt::test('settings.password')
            ->set('current_password', 'password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword');

        $response->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password', $superadmin->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $superadmin = Superadmin::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($superadmin);

        $response = Volt::test('settings.password')
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('updatePassword');

        $response->assertHasErrors(['current_password']);
    }
}
