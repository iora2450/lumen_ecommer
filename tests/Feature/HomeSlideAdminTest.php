<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\HomeSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeSlideAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_banner_and_link_it_to_a_category(): void
    {
        Storage::fake('banners');

        $category = Category::create([
            'name' => 'Paneles LED',
            'slug' => 'paneles-led',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs(User::factory()->create())
            ->post(route('admin.home-slides.store'), [
                'badge' => 'Promoción',
                'title' => 'Paneles LED con descuento',
                'subtitle' => 'Precios especiales por tiempo limitado.',
                'image' => UploadedFile::fake()->image('paneles.jpg', 1600, 700),
                'category_id' => $category->id,
                'button_text' => 'Ver productos',
                'sort_order' => 1,
                'is_active' => '1',
            ]);

        $slide = HomeSlide::sole();

        $response->assertRedirect(route('admin.home-slides.index'));
        $this->assertSame($category->id, $slide->category_id);
        $this->assertSame('/catalogo?category=paneles-led', $slide->link_url);
        $this->assertStringStartsWith('/images/banners/', $slide->image_url);
        Storage::disk('banners')->assertExists(basename($slide->image_url));
    }

    public function test_banner_form_explains_upload_and_category_selection(): void
    {
        Category::create([
            'name' => 'Reflectores',
            'slug' => 'reflectores',
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.home-slides.create'))
            ->assertOk()
            ->assertSee('Seleccionar imagen')
            ->assertSee('Ofertas activas')
            ->assertSee('Categoría promocionada')
            ->assertSee('Reflectores')
            ->assertSee('enctype="multipart/form-data"', false);
    }
}
