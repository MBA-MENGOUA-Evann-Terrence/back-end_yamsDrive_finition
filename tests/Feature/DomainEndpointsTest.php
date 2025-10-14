<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DomainEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_domains_returns_200(): void
    {
        $response = $this->getJson('/api/domains');
        $response->assertStatus(200);
    }

    public function test_create_domain_works_unauthenticated_and_authenticated(): void
    {
        // Unauthenticated
        $name1 = 'example-' . Str::random(8) . '.com';
        $res1 = $this->postJson('/api/domains', ['name' => $name1]);
        $res1->assertStatus(201)
            ->assertJsonStructure(['message', 'domain' => ['id', 'name', 'status', 'expires_at']]);

        // Authenticated
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $name2 = 'example-' . Str::random(8) . '.net';
        $res2 = $this->postJson('/api/domains', ['name' => $name2]);
        $res2->assertStatus(201)
            ->assertJsonPath('domain.user_id', $user->id);
    }
}
