<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SiteSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
    }

    public function test_json_feed_returns_200_and_structure(): void
    {
        $response = $this->getJson('/api/feeds/json');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure(['products' => [['id', 'name', 'in_stock']]]);

        $products = $response->json('products');
        $this->assertNotEmpty($products);
        $first = $products[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('in_stock', $first);
    }

    public function test_xml_feed_returns_200_and_structure(): void
    {
        $response = $this->get('/api/feeds/xml');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml');

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('products', $xml->getName());
        $this->assertGreaterThan(0, count($xml->product));
        $first = $xml->product[0];
        $this->assertNotNull($first->id);
        $this->assertNotNull($first->name);
        $this->assertNotNull($first->in_stock);
    }

    public function test_feed_in_stock_reflects_product_stock(): void
    {
        $product = Product::query()->first();
        $product->update(['stock' => 0]);

        $response = $this->getJson('/api/feeds/json');
        $products = $response->json('products');
        $found = collect($products)->firstWhere('id', $product->id);
        $this->assertFalse($found['in_stock']);
    }
}
