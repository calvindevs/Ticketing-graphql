<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_authenticated_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@email.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@email.com',
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_remains_guest_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'admin@email.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@email.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(302);
        $this->assertGuest();
    }

    public function test_authenticated_user_role_matches_seeded_role(): void
    {
        $expectedRole = 'agent';

        $user = User::factory()->create([
            'email' => 'agent@email.com',
            'password' => Hash::make('password'),
            'role' => $expectedRole,
        ]);

        $response = $this->post('/login', [
            'email' => 'agent@email.com',
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $this->assertAuthenticated();
        $this->assertSame($expectedRole, Auth::user()->role);
        $this->assertTrue(Auth::user()->is($user));
    }
}
