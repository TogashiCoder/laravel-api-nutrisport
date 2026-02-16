<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Site::query()->firstOrCreate(
            ['code' => 'fr'],
            ['domain' => 'nutri-sport.fr', 'currency' => 'EUR']
        );
    }

    public function test_user_can_register(): void
    {
        $site = Site::query()->first();
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'site_id' => $site->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user', 'token', 'expires_in'])
            ->assertJsonPath('user.email', 'test@example.com');
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'site_id' => $site->id]);
    }

    public function test_user_can_login(): void
    {
        $site = Site::query()->first();
        User::query()->create([
            'name' => 'Test',
            'email' => 'login@example.com',
            'password' => bcrypt('secret'),
            'site_id' => $site->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'secret',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token', 'expires_in']);
    }

    public function test_agent_can_login(): void
    {
        $this->seed(\Database\Seeders\AgentSeeder::class);

        $response = $this->postJson('/api/agent/login', [
            'email' => 'agent@nutrisport.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'expires_in']);
    }
}
