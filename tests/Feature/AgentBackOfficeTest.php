<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Order;
use App\Models\Product;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentBackOfficeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SiteSeeder::class);
        $this->seed(\Database\Seeders\AgentSeeder::class);
    }

    public function test_agent_id_1_can_list_orders_last_5_days(): void
    {
        $site = Site::query()->first();
        $user = User::query()->create([
            'name' => 'Customer One',
            'email' => 'c1@example.com',
            'password' => bcrypt('pass'),
            'site_id' => $site->id,
        ]);
        $address = \App\Models\Address::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Customer One',
            'address_line' => '10 rue Test',
            'city' => 'Paris',
            'country' => 'France',
        ]);
        Order::query()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'address_id' => $address->id,
            'total' => 99.99,
            'status' => 'pending',
            'remaining_amount' => 99.99,
        ]);

        $login = $this->postJson('/api/agent/login', [
            'email' => 'agent@nutrisport.com',
            'password' => 'password',
        ]);
        $token = $login->json('token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/agent/orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('meta.per_page', 20);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('customer_name', $data[0]);
        $this->assertArrayHasKey('total', $data[0]);
        $this->assertArrayHasKey('status', $data[0]);
        $this->assertArrayHasKey('remaining_amount', $data[0]);
    }

    public function test_agent_id_1_can_create_product_with_prices_per_site(): void
    {
        $login = $this->postJson('/api/agent/login', [
            'email' => 'agent@nutrisport.com',
            'password' => 'password',
        ]);
        $token = $login->json('token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/agent/products', [
                'name' => 'New Supplement',
                'stock' => 50,
                'prices' => [
                    'fr' => 29.99,
                    'it' => 31.50,
                    'be' => 30.00,
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('product.name', 'New Supplement')
            ->assertJsonPath('product.stock', 50);
        $this->assertSame(29.99, (float) $response->json('product.prices.fr'));
        $this->assertSame(31.5, (float) $response->json('product.prices.it'));
        $this->assertSame(30.0, (float) $response->json('product.prices.be'));

        $this->assertDatabaseHas('products', ['name' => 'New Supplement', 'stock' => 50]);
        $product = Product::query()->where('name', 'New Supplement')->first();
        $this->assertSame(3, $product->sitePrices()->count());
    }

    public function test_agent_orders_requires_auth(): void
    {
        $response = $this->getJson('/api/agent/orders');
        $response->assertStatus(401);
    }

    public function test_agent_products_requires_auth(): void
    {
        $response = $this->postJson('/api/agent/products', [
            'name' => 'Test',
            'stock' => 0,
            'prices' => ['fr' => 10, 'it' => 10, 'be' => 10],
        ]);
        $response->assertStatus(401);
    }
}
