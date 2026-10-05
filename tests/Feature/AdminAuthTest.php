<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_the_admin_login_form(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Iniciar sesión');
    }

    public function test_authenticated_user_visiting_admin_login_is_sent_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
