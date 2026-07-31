<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'password_user' => 'required|string|min:8|confirmed',
            'nohp_user'     => 'nullable|string|regex:/^[0-9]+$/|min:10|max:15',
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

        // Di environment local (development/demo), langsung verifikasi email otomatis
        // tanpa perlu klik link di inbox. Di production, user tetap harus verifikasi.
        if (app()->environment('local', 'testing')) {
            /** @var \App\Models\User $user */
            $user->markEmailAsVerified();

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran berhasil. Akun Anda langsung aktif (mode development).',
                'data'    => null,
                'errors'  => null,
            ], 201);
        }

        // Production: kirim email verifikasi, user harus klik link sebelum login
        event(new \Illuminate\Auth\Events\Registered($user));

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil. Silakan periksa kotak masuk email Anda untuk memverifikasi akun sebelum login.',
            'data'    => null,
            'errors'  => null,
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

        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth('api');

        if (!$token = $auth->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password',
                'data'    => null,
                'errors'  => null,
            ], 401);
        }

        $user = $auth->user();



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
        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth('api');
        $auth->logout();

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
        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth('api');

        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved',
            'data'    => $auth->user(),
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
        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth('api');
        $token = $auth->refresh();

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
            /** @var \Tymon\JWTAuth\JWTGuard $auth */
            $auth = auth('api');
            $user = $auth->user();

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

    /**
     * Verify email address using Laravel temporary signed URL.
     *
     * GET /api/auth/verify/{id}/{hash}
     */
    public function verify(Request $request, $id, $hash)
    {
        // 1. Manually check signature validation since JWT is stateless
        if (! $request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Tautan verifikasi tidak valid atau telah kedaluwarsa.',
                'data'    => null,
                'errors'  => null,
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = User::findOrFail($id);

        // 2. Validate hash mapping
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'success' => false,
                'message' => 'Token verifikasi email tidak cocok.',
                'data'    => null,
                'errors'  => null,
            ], 401);
        }

        // 3. Mark as verified if not already
        if ($user->hasVerifiedEmail()) {
            return redirect()->away('http://localhost:5173/login?verified=already');
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        // 4. Redirect user to frontend login page with verify success indicator
        return redirect()->away('http://localhost:5173/login?verified=success');
    }
}