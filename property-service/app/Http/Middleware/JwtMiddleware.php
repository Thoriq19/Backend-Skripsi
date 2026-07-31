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
        // Allow simulated gateway parameters in testing env
        if (app()->environment('testing') && ($request->has('auth_user_role') || $request->hasHeader('Authorization') === false)) {
            $request->merge([
                'auth_user_id'   => $request->input('auth_user_id') ?? $request->input('id_user') ?? 1,
                'auth_user_role' => $request->input('auth_user_role') ?? 'owner',
                'auth_user_name' => $request->input('auth_user_name') ?? 'Test User',
                'auth_user_email'=> $request->input('auth_user_email') ?? 'test@test.com',
            ]);
            return $next($request);
        }

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

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);
            return response()->json($body ?? [
                'success' => false,
                'message' => 'Unauthorized',
                'data'    => null,
                'errors'  => null,
            ], $response->getStatusCode());
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
