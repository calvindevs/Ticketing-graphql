<?php

namespace Tests\Feature\Graphql;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MeQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_query_me(): void
    {
        $user = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@email.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->post('/login', [
            'email' => 'admin@email.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/graphql', [
            'query' => '
                query {
                    me {
                        id 
                        name 
                        email 
                        role
                    }
                }',
        ]);
        $response->dump();
        $response->assertStatus(200)
            ->assertJsonPath('data.me.email', 'admin@email.com')
            ->assertJsonPath('data.me.role', 'admin')
            ->assertJsonMissingPath('errors');
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_cannot_query_me(): void
    {
        $response = $this->postJson('/graphql', [
            'query' => '{ me { id email role } }',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.me', null)
            ->assertJsonStructure(['errors']);
    }
}
