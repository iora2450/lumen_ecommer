<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_footer_maintenance_module(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.footer.edit'))
            ->assertOk()
            ->assertSee('Pie de página')
            ->assertSee('Descripción corta')
            ->assertSee('Correo electrónico')
            ->assertSee('Lunes a viernes');
    }

    public function test_admin_can_update_all_footer_information(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->patch(route('admin.footer.update'), [
                'footer_description' => 'Soluciones de iluminación para cada proyecto.',
                'footer_email' => 'ventas@lumens.com.sv',
                'footer_phone' => '+503 2200 1100',
                'footer_location' => 'Antiguo Cuscatlán, La Libertad',
                'footer_weekday_hours' => 'Lun-Vie: 7:30am - 5:30pm',
                'footer_saturday_hours' => 'Sáb: 8:00am - 12:00pm',
            ]);

        $response
            ->assertRedirect(route('admin.footer.edit'))
            ->assertSessionHas('success', 'La información del pie de página fue actualizada.');

        $this->assertSame('footer', Setting::where('key', 'footer_description')->value('group'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Soluciones de iluminación para cada proyecto.')
            ->assertSee('ventas@lumens.com.sv')
            ->assertSee('+503 2200 1100')
            ->assertSee('Antiguo Cuscatlán, La Libertad')
            ->assertSee('Lun-Vie: 7:30am - 5:30pm')
            ->assertSee('Sáb: 8:00am - 12:00pm');
    }
}
