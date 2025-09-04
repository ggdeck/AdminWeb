<?php

namespace Tests\Feature\Settings;

use App\Models\Superadmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($superadmin = Superadmin::factory()->create());

        $this->get(route('settings.profile'))->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $superadmin = Superadmin::factory()->create();

        $this->actingAs($superadmin);

        $response = Volt::test('settings.profile')
            ->set('name', 'Test Superadmin')
            ->set('email', 'superadmin@example.com')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $superadmin->refresh();

        $this->assertEquals('Test Superadmin', $superadmin->name);
        $this->assertEquals('superadmin@example.com', $superadmin->email);
        $this->assertNull($superadmin->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $superadmin = Superadmin::factory()->create();

        $this->actingAs($superadmin);

        $response = Volt::test('settings.profile')
            ->set('name', 'Test Superadmin')
            ->set('email', $superadmin->email)
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull($superadmin->refresh()->email_verified_at);
    }

    public function test_superadmin_can_delete_their_account(): void
    {
        $superadmin = Superadmin::factory()->create([
            'password' => 'password', // pakai mutator, otomatis hash
        ]);

        $this->actingAs($superadmin);

        $response = Volt::test('settings.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($superadmin->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $superadmin = Superadmin::factory()->create([
            'password' => 'password',
        ]);

        $this->actingAs($superadmin);

        $response = Volt::test('settings.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $this->assertNotNull($superadmin->fresh());
    }
}
