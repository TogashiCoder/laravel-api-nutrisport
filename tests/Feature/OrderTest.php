<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Mail\OrderConfirmationAdmin;
use App\Mail\OrderConfirmationClient;
use App\Models\Address;
use App\Models\Product;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SiteSeeder::class);
        $this->seed(\Database\Seeders\ProductSeeder::class);
        $this->seed(\Database\Seeders\ProductSitePriceSeeder::class);
    }

    public function test_authenticated_user_can_create_order_from_cart(): void
    {
        Mail::fake();
        Event::fake([OrderCreated::class]);
        config(['mail.admin_email' => 'admin@test.com']);

        $site = Site::query()->first();
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'order@example.com',
            'password' => bcrypt('password'),
            'site_id' => $site->id,
        ]);

        $product = Product::query()->first();
        $addCart = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
            'site_id' => $site->id,
        ]);
        $cartToken = $addCart->headers->get('X-Cart-Token');

        $login = $this->postJson('/api/login', ['email' => 'order@example.com', 'password' => 'password']);
        $token = $login->json('token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Cart-Token', $cartToken)
            ->postJson('/api/orders', [
                'payment_method' => 'bank_transfer',
                'address' => [
                    'full_name' => 'Jean Dupont',
                    'address_line' => '10 rue de la Paix',
                    'city' => 'Paris',
                    'country' => 'France',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['order' => ['id', 'total', 'status', 'content']])
            ->assertJsonPath('order.status', 'pending');

        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
        Mail::assertSent(OrderConfirmationClient::class);
        Mail::assertSent(OrderConfirmationAdmin::class);
        Event::assertDispatched(OrderCreated::class);
    }

    public function test_order_requires_authentication(): void
    {
        $response = $this->postJson('/api/orders', [
            'payment_method' => 'bank_transfer',
            'address' => [
                'full_name' => 'Jean',
                'address_line' => '10 rue',
                'city' => 'Paris',
                'country' => 'France',
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_order_rejects_empty_cart(): void
    {
        $site = Site::query()->first();
        $user = User::query()->create([
            'name' => 'Test',
            'email' => 'empty@example.com',
            'password' => bcrypt('password'),
            'site_id' => $site->id,
        ]);

        $login = $this->postJson('/api/login', ['email' => 'empty@example.com', 'password' => 'password']);
        $token = $login->json('token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/orders', [
                'payment_method' => 'bank_transfer',
                'address' => [
                    'full_name' => 'Jean',
                    'address_line' => '10 rue',
                    'city' => 'Paris',
                    'country' => 'France',
                ],
            ]);

        $response->assertStatus(422)->assertJson(['message' => 'Cart is empty.']);
    }

    public function test_order_decrements_stock_and_clears_cart(): void
    {
        Mail::fake();
        Event::fake([OrderCreated::class]);

        $site = Site::query()->first();
        $user = User::query()->create([
            'name' => 'Stock User',
            'email' => 'stock@example.com',
            'password' => bcrypt('password'),
            'site_id' => $site->id,
        ]);

        $product = Product::query()->first();
        $stockBefore = $product->stock;

        $addCart = $this->postJson('/api/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'site_id' => $site->id,
        ]);
        $cartToken = $addCart->headers->get('X-Cart-Token');

        $login = $this->postJson('/api/login', ['email' => 'stock@example.com', 'password' => 'password']);
        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Cart-Token', $cartToken)
            ->postJson('/api/orders', [
                'payment_method' => 'bank_transfer',
                'address' => [
                    'full_name' => 'Jean',
                    'address_line' => '10 rue',
                    'city' => 'Paris',
                    'country' => 'France',
                ],
            ])
            ->assertStatus(201);

        $this->assertSame($stockBefore - 3, Product::query()->find($product->id)->stock);

        $getCart = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Cart-Token', $cartToken)
            ->getJson('/api/cart');
        $getCart->assertJsonPath('cart.item_count', 0);
    }
}
