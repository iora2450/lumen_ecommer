<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFilterMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_uses_a_sticky_sidebar_with_an_independent_scroll_region(): void
    {
        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('id="filters-sidebar"', false)
            ->assertSee('lg:sticky', false)
            ->assertSee('id="filters-scroll-region"', false)
            ->assertSee('catalog-filter-scroll', false)
            ->assertSee('catalog-filter-scroll-position', false);
    }
}
