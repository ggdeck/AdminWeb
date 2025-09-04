<?php

namespace Tests\Feature\Auth;

use App\Models\Superadmin;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $superadmin = Superadmin::factory()->unverified()->create();

        $response = $this->actingAs($superadmin)->get(route('verification.notice'));

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $superadmin = Superadmin::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $superadmin->id, 'hash' => sha1($superadmin->email)]
        );

        $response = $this->actingAs($superadmin)->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        $this->assertTrue($superadmin->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $superadmin = Superadmin::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $superadmin->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($superadmin)->get($verificationUrl);

        $this->assertFalse($superadmin->fresh()->hasVerifiedEmail());
    }

    public function test_already_verified_superadmin_visiting_verification_link_is_redirected_without_firing_event_again(): void
    {
        $superadmin = Superadmin::factory()->create([
            'email_verified_at' => now(),
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $superadmin->id, 'hash' => sha1($superadmin->email)]
        );

        $this->actingAs($superadmin)->get($verificationUrl)
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $this->assertTrue($superadmin->fresh()->hasVerifiedEmail());
        Event::assertNotDispatched(Verified::class);
    }
}
