<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get all users.
     *
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $users = User::paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data'    => $users,
            'errors'  => null,
        ]);
    }

    /**
     * Get a specific user by ID.
     *
     * GET /api/users/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data'    => $user,
            'errors'  => null,
        ]);
    }

    /**
     * Update a user.
     *
     * PUT /api/users/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_user'  => 'sometimes|string|max:255',
            'email_user' => 'sometimes|string|email|max:255|unique:users,email_user,' . $id,
            'nohp_user'  => 'nullable|string|max:20',
            'role'       => 'sometimes|in:pengelola_kos,owner,user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user->update($request->only(['nama_user', 'email_user', 'nohp_user', 'role']));

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data'    => $user->fresh(),
            'errors'  => null,
        ]);
    }

    /**
     * Delete a user (soft delete).
     *
     * DELETE /api/users/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
            'data'    => null,
            'errors'  => null,
        ]);
    }

    /**
     * Create a pengelola kos account (Owner only).
     *
     * Only authenticated users with the 'owner' role can create pengelola kos accounts.
     * The role is hardcoded to 'pengelola_kos' and cannot be changed via request.
     *
     * POST /api/users/create-pengelola-kos
     */
    public function createPengelolaKos(Request $request): JsonResponse
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

        $pengelolaKos = User::create([
            'nama_user'     => $request->nama_user,
            'email_user'    => $request->email_user,
            'password_user' => $request->password_user,
            'role'          => 'pengelola_kos',
            'nohp_user'     => $request->nohp_user,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengelola Kos account created successfully',
            'data'    => $pengelolaKos,
            'errors'  => null,
        ], 201);
    }

    /**
     * Create a user account (Pengelola Kos only).
     *
     * Only authenticated users with the 'pengelola_kos' role can create user accounts.
     * The role is hardcoded to 'user' and cannot be changed via request.
     *
     * POST /api/users/create-user
     */
    public function createUser(Request $request): JsonResponse
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
            'role'          => 'user',
            'nohp_user'     => $request->nohp_user,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User account created successfully',
            'data'    => $user,
            'errors'  => null,
        ], 201);
    }
}
