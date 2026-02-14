<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductSitePrice;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SiteSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
        $this->seed(\Database\Seeders\ProductSitePriceSeeder::class);
    }

    public function test_product_list_requires_site_id(): void
    {
        $response = $this->getJson('/api/products');
        $response->assertStatus(422);
    }

    public function test_product_list_returns_paginated_products_with_site_price(): void
    {
        $site = Site::query()->first();
        $response = $this->getJson('/api/products?site_id=' . $site->id);

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonStructure(['data' => [['id', 'name', 'price', 'in_stock']]])
            ->assertJsonPath('meta.per_page', 15);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('price', $first);
        $this->assertArrayHasKey('in_stock', $first);
    }

    public function test_product_list_respects_per_page(): void
    {
        $site = Site::query()->first();
        $response = $this->getJson('/api/products?site_id=' . $site->id . '&per_page=3');
        $response->assertStatus(200)->assertJsonPath('meta.per_page', 3);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_product_detail_requires_site_id(): void
    {
        $product = Product::query()->first();
        $response = $this->getJson('/api/products/' . $product->id);
        $response->assertStatus(422);
    }

    public function test_product_detail_returns_product_with_price_and_in_stock(): void
    {
        $site = Site::query()->first();
        $product = Product::query()->first();
        $response = $this->getJson('/api/products/' . $product->id . '?site_id=' . $site->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name)
            ->assertJsonPath('data.in_stock', $product->stock > 0)
            ->assertJsonStructure(['data' => ['id', 'name', 'price', 'in_stock', 'description']]);
    }

    public function test_product_detail_returns_404_when_product_not_found(): void
    {
        $site = Site::query()->first();
        $response = $this->getJson('/api/products/99999?site_id=' . $site->id);
        $response->assertStatus(404);
    }

    public function test_product_detail_returns_404_when_no_price_for_site(): void
    {
        $site = Site::query()->first();
        $product = Product::query()->first();
        ProductSitePrice::query()->where('product_id', $product->id)->where('site_id', $site->id)->delete();

        $response = $this->getJson('/api/products/' . $product->id . '?site_id=' . $site->id);
        $response->assertStatus(404);
    }
}
