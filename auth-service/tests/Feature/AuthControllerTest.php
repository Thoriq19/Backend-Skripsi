<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test register owner account in local environment.
     */
    public function test_register_owner_success_in_local_env()
    {
        $response = $this->postJson('/api/auth/register', [
            'nama_user' => 'Test Owner',
            'email_user' => 'owner@test.com',
            'password_user' => 'password123',
            'password_user_confirmation' => 'password123',
            'nohp_user' => '08123456789',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Pendaftaran berhasil. Akun Anda langsung aktif (mode development).',
                 ]);

        $this->assertDatabaseHas('users', [
            'email_user' => 'owner@test.com',
            'role' => 'owner',
        ]);

        $user = User::where('email_user', 'owner@test.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    /**
     * Test login returns JWT token for verified user.
     */
    public function test_login_success_returns_jwt_token()
    {
        $user = User::create([
            'nama_user' => 'Test User',
            'email_user' => 'user@test.com',
            'password_user' => 'password123',
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email_user' => 'user@test.com',
            'password_user' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Login successful',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'user',
                         'token',
                         'token_type',
                         'expires_in',
                     ]
                 ]);
    }

    /**
     * Test login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials()
    {
        $user = User::create([
            'nama_user' => 'Test User',
            'email_user' => 'user@test.com',
            'password_user' => 'password123',
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email_user' => 'user@test.com',
            'password_user' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid email or password',
                 ]);
    }

    /**
     * Test login unverified owner is forbidden.
     */
    public function test_login_unverified_owner_forbidden()
    {
        $user = User::create([
            'nama_user' => 'Test Unverified Owner',
            'email_user' => 'unverified@test.com',
            'password_user' => 'password123',
            'role' => 'owner',
            'email_verified_at' => null, // Not verified
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email_user' => 'unverified@test.com',
            'password_user' => 'password123',
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Alamat email Anda belum diverifikasi. Silakan periksa kotak masuk email Anda.',
                 ]);
    }

    /**
     * Test validate token endpoint validates JWT token correctly.
     */
    public function test_validate_token_endpoint()
    {
        $user = User::create([
            'nama_user' => 'Test User',
            'email_user' => 'user@test.com',
            'password_user' => 'password123',
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/auth/validate-token');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Token is valid',
                     'data' => [
                         'user_id' => $user->id,
                         'email_user' => $user->email_user,
                         'role' => $user->role,
                     ]
                 ]);
    }
}
