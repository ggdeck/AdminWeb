<?php

namespace Tests\Feature;

use App\Models\Superadmin; // ⬅️ ganti User → Superadmin
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_superadmins_can_visit_the_dashboard(): void
    {
        $superadmin = Superadmin::factory()->create(); // ⬅️ pakai Superadmin factory
        $this->actingAs($superadmin, 'web'); // ⬅️ pakai guard web (provider superadmins)

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
    }
}
