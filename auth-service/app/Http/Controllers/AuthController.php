<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register a new owner account.
     *
     * This is the only public registration endpoint.
     * Only owner accounts can be created through public registration.
     * Pengelola Kos accounts are created by owners, and user accounts are created by pengelola kos.
     *
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_user'     => 'required|string|max:255',
            'email_user'    => 'required|string|email|max:255|unique:users,email_user',
            'password_user' => 'required|string|min:6|confirmed',
            'nohp_user'     => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'nama_user'     => $request->nama_user,
            'email_user'    => $request->email_user,
            'password_user' => $request->password_user,
            'role'          => 'owner',
            'nohp_user'     => $request->nohp_user,
        ]);

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->login($user);

        return response()->json([
            'success' => true,
            'message' => 'Owner registered successfully',
            'data'    => [
                'user'  => $user,
                'token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => config('jwt.ttl') * 60,
            ],
            'errors' => null,
        ], 201);
    }

    /**
     * Login user and return JWT token.
     *
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email_user'    => 'required|string|email',
            'password_user' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $credentials = [
            'email_user' => $request->email_user,
            'password'   => $request->password_user,
        ];

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password',
                'data'    => null,
                'errors'  => null,
            ], 401);
        }

        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data'    => [
                'user'  => $user,
                'token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => config('jwt.ttl') * 60,
            ],
            'errors' => null,
        ]);
    }

    /**
     * Logout user (invalidate the token).
     *
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
            'data'    => null,
            'errors'  => null,
        ]);
    }

    /**
     * Get the authenticated user profile.
     *
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved',
            'data'    => auth('api')->user(),
            'errors'  => null,
        ]);
    }

    /**
     * Refresh the JWT token.
     *
     * POST /api/auth/refresh
     */
    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed',
            'data'    => [
                'token'      => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
            'errors' => null,
        ]);
    }

    /**
     * Validate a JWT token (internal endpoint for API Gateway).
     *
     * POST /api/auth/validate-token
     */
    public function validateToken(): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'data'    => null,
                    'errors'  => null,
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token is valid',
                'data'    => [
                    'user_id'    => $user->id,
                    'email_user' => $user->email_user,
                    'role'       => $user->role,
                    'nama_user'  => $user->nama_user,
                ],
                'errors' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token validation failed',
                'data'    => null,
                'errors'  => $e->getMessage(),
            ], 401);
        }
    }
}