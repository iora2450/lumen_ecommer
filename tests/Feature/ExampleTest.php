<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200)
            ->assertSee('Iluminando con calidad')
            ->assertDontSee('Iluminación que transforma tus espacios')
            ->assertSee('Cómo comprar')
            ->assertSee('id="como-comprar"', false)
            ->assertSee('Explora el catálogo')
            ->assertSee('Agrega al carrito')
            ->assertSee('Completa tus datos')
            ->assertSee('Recibe confirmación');
    }
}
