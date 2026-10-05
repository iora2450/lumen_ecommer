<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroSlideAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_main_banner(): void
    {
        Storage::fake('banners');

        $response = $this
            ->actingAs(User::factory()->create())
            ->post(route('admin.hero-slides.store'), [
                'badge' => 'Nuevo',
                'title' => 'Iluminación para grandes proyectos',
                'subtitle' => 'Conoce nuestras soluciones profesionales.',
                'image' => UploadedFile::fake()->image('principal.jpg', 1920, 760),
                'link_url' => '/catalogo',
                'button_text' => 'Ver catálogo',
                'sort_order' => 1,
                'is_active' => '1',
            ]);

        $slide = HeroSlide::sole();

        $response->assertRedirect(route('admin.hero-slides.index'));
        $this->assertTrue($slide->is_active);
        $this->assertStringStartsWith('/images/banners/', $slide->image_url);
        Storage::disk('banners')->assertExists(basename($slide->image_url));
    }

    public function test_home_renders_active_main_banners_as_a_carousel(): void
    {
        HeroSlide::create([
            'title' => 'Primer banner',
            'image_url' => '/images/banner-1.jpg',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        HeroSlide::create([
            'title' => 'Segundo banner',
            'image_url' => '/images/banner-2.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        HeroSlide::create([
            'title' => 'Banner oculto',
            'image_url' => '/images/banner-3.jpg',
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-hero-carousel', false)
            ->assertSeeInOrder(['Segundo banner', 'Primer banner'])
            ->assertDontSee('Banner oculto');
    }

    public function test_admin_can_send_a_main_banner_to_the_active_offers_page(): void
    {
        Storage::fake('banners');

        $response = $this
            ->actingAs(User::factory()->create())
            ->post(route('admin.hero-slides.store'), [
                'badge' => 'Oferta especial',
                'title' => 'Ahorra en iluminación seleccionada',
                'subtitle' => 'Precios especiales por tiempo limitado.',
                'image' => UploadedFile::fake()->image('oferta.jpg', 1920, 760),
                'destination' => 'offers',
                'button_text' => 'Ver ofertas',
                'sort_order' => 1,
                'is_active' => '1',
            ]);

        $slide = HeroSlide::sole();

        $response->assertRedirect(route('admin.hero-slides.index'));
        $this->assertSame('/ofertas', $slide->link_url);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ahorra en iluminación seleccionada')
            ->assertSee('href="/ofertas"', false)
            ->assertSee('Ver ofertas');
    }

    public function test_admin_navigation_has_separate_main_and_news_banner_sections(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.hero-slides.index'))
            ->assertOk()
            ->assertSee('Banner principal')
            ->assertSee('Banners de novedades');
    }
}
