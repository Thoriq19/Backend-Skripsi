<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Response;

/**
 * JwtMiddleware - Validates JWT token via Auth Service
 *
 * This middleware calls the Auth Service to validate the JWT token
 * instead of handling JWT locally. This is the inter-service
 * authentication pattern.
 */
class JwtMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided',
                'data'    => null,
                'errors'  => null,
            ], 401);
        }

        try {
            $client = new Client([
                'base_uri' => env('AUTH_SERVICE_URL', 'http://localhost:8001'),
                'timeout'  => 5,
            ]);

            $response = $client->post('/api/auth/validate-token', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if ($body['success'] ?? false) {
                // Attach user data to the request for downstream use
                $request->merge([
                    'auth_user_id'   => $body['data']['user_id'] ?? null,
                    'auth_user_role' => $body['data']['role'] ?? null,
                    'auth_user_name' => $body['data']['name'] ?? null,
                    'auth_user_email'=> $body['data']['email'] ?? null,
                ]);

                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
                'data'    => null,
                'errors'  => null,
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Auth service unavailable: ' . $e->getMessage(),
                'data'    => null,
                'errors'  => null,
            ], 503);
        }
    }
}
