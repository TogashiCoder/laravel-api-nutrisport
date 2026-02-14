<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductSitePrice;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SiteSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
        $this->seed(\Database\Seeders\ProductSitePriceSeeder::class);
    }

    public function test_guest_can_add_item_and_get_cart_token(): void
    {
        $site = Site::query()->first();
        $product = Product::query()->first();

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'site_id' => $site->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['cart' => ['items', 'subtotal', 'total', 'item_count']])
            ->assertJsonPath('cart.item_count', 2)
            ->assertHeader('X-Cart-Token');

        $token = $response->headers->get('X-Cart-Token');
        $this->assertNotEmpty($token);
    }

    public function test_add_item_stores_price_snapshot(): void
    {
        $site = Site::query()->first();
        $product = Product::query()->first();
        $price = ProductSitePrice::query()
            ->where('product_id', $product->id)
            ->where('site_id', $site->id)
            ->first();

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'site_id' => $site->id,
        ]);

        $response->assertStatus(201);
        $items = $response->json('cart.items');
        $this->assertCount(1, $items);
        $this->assertSame((float) $price->price, $items[0]['price']);
        $this->assertSame(1, $items[0]['quantity']);
    }

    public function test_guest_can_get_cart_with_token(): void
    {
        $site = Site::query()->first();
        $product = Product::query()->first();

        $add = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'site_id' => $site->id,
        ]);
        $token = $add->headers->get('X-Cart-Token');

        $get = $this->getJson('/api/cart', ['X-Cart-Token' => $token]);
        $get->assertStatus(200)
            ->assertJsonPath('cart.item_count', 1)
            ->assertHeader('X-Cart-Token', $token);
    }

    public function test_add_item_updates_quantity(): void
    {
        $site = Site::query()->first();
        $product = Product::query()->first();

        $r1 = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'site_id' => $site->id,
        ]);
        $token = $r1->headers->get('X-Cart-Token');

        $r2 = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'site_id' => $site->id,
        ], ['X-Cart-Token' => $token]);

        $r2->assertStatus(201)->assertJsonPath('cart.item_count', 3);
    }

    public function test_remove_item(): void
    {
        $site = Site::query()->first();
        $product = Product::query()->first();

        $add = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'site_id' => $site->id,
        ]);
        $token = $add->headers->get('X-Cart-Token');

        $remove = $this->deleteJson('/api/cart/items/' . $product->id, [], ['X-Cart-Token' => $token]);
        $remove->assertStatus(200)->assertJsonPath('cart.item_count', 0);
        $this->assertCount(0, $remove->json('cart.items'));
    }

    public function test_clear_cart(): void
    {
        $site = Site::query()->first();
        $product = Product::query()->first();

        $add = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'site_id' => $site->id,
        ]);
        $token = $add->headers->get('X-Cart-Token');

        $clear = $this->deleteJson('/api/cart', [], ['X-Cart-Token' => $token]);
        $clear->assertStatus(200)->assertJson(['message' => 'Cart cleared.']);

        $get = $this->getJson('/api/cart', ['X-Cart-Token' => $token]);
        $get->assertStatus(200)->assertJsonPath('cart.item_count', 0);
    }

    public function test_add_item_validates_stock(): void
    {
        $site = Site::query()->first();
        $product = Product::query()->first();
        $product->update(['stock' => 0]);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'site_id' => $site->id,
        ]);

        $response->assertStatus(422)->assertJson(['message' => 'Product out of stock.']);
    }

    public function test_add_item_reduces_quantity_to_available_stock(): void
    {
        $site = Site::query()->first();
        $product = Product::query()->first();
        $product->update(['stock' => 2]);

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 10,
            'site_id' => $site->id,
        ]);

        $response->assertStatus(201)->assertJsonPath('cart.item_count', 2);
    }

    public function test_add_item_rejects_invalid_site(): void
    {
        $product = Product::query()->first();

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'site_id' => 99999,
        ]);

        $response->assertStatus(422);
    }

    public function test_add_item_rejects_product_without_price_for_site(): void
    {
        $site = Site::query()->first();
        $product = Product::query()->first();
        ProductSitePrice::query()
            ->where('product_id', $product->id)
            ->where('site_id', $site->id)
            ->delete();

        $response = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'site_id' => $site->id,
        ]);

        $response->assertStatus(422)->assertJson(['message' => 'Product not available for this site.']);
    }

    public function test_cart_ttl_is_configured(): void
    {
        $this->assertSame(259200, config('cart.ttl'));
    }
}
