<?php

namespace Tests\Unit;

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test RoleMiddleware blocks unauthenticated user.
     */
    public function test_blocks_unauthenticated_user()
    {
        $middleware = new RoleMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Allowed');
        }, 'owner');

        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test RoleMiddleware allows user with correct role.
     */
    public function test_allows_matching_role()
    {
        $user = User::create([
            'nama_user' => 'Owner User',
            'email_user' => 'owner@test.com',
            'password_user' => 'password123',
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        auth('api')->login($user);

        $middleware = new RoleMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Allowed');
        }, 'owner');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Allowed', $response->getContent());
    }

    /**
     * Test RoleMiddleware blocks user with incorrect role.
     */
    public function test_blocks_incorrect_role()
    {
        $user = User::create([
            'nama_user' => 'Pengelola User',
            'email_user' => 'pengelola@test.com',
            'password_user' => 'password123',
            'role' => 'pengelola_kos',
            'email_verified_at' => now(),
        ]);

        auth('api')->login($user);

        $middleware = new RoleMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Allowed');
        }, 'owner');

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test RoleMiddleware allows user if role is one of pipe separated roles.
     */
    public function test_allows_matching_role_with_multiple_roles()
    {
        $user = User::create([
            'nama_user' => 'Pengelola User',
            'email_user' => 'pengelola@test.com',
            'password_user' => 'password123',
            'role' => 'pengelola_kos',
            'email_verified_at' => now(),
        ]);

        auth('api')->login($user);

        $middleware = new RoleMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Allowed');
        }, 'owner|pengelola_kos');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Allowed', $response->getContent());
    }
}
