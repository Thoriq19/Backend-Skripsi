<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Get all users.
     *
     * GET /api/users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Filter berdasarkan role (misal: ?role=user untuk rekap penghuni)
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

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
        $this->handleBase64Ktp($request);
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
            'nama_user'          => 'sometimes|string|max:255',
            'email_user'         => 'sometimes|string|email|max:255|unique:users,email_user,' . $id,
            'nohp_user'          => 'nullable|string|regex:/^[0-9]+$/|min:10|max:15',
            'role'               => 'sometimes|in:pengelola_kos,owner,user',
            'password_user'      => 'nullable|string|min:6|confirmed',
            'dokumen_pendukung'  => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $updateData = $request->only(['nama_user', 'email_user', 'nohp_user', 'role', 'dokumen_pendukung']);
        if ($request->filled('password_user')) {
            $updateData['password_user'] = $request->password_user;
        }

        $user->update($updateData);

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

        $pengelolaKos = User::create([
            'nama_user'     => $request->nama_user,
            'email_user'    => $request->email_user,
            'password_user' => $request->password_user,
            'role'          => 'pengelola_kos',
            'nohp_user'     => $request->nohp_user,
            'email_verified_at' => now(),
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
        $this->handleBase64Ktp($request);
        $validator = Validator::make($request->all(), [
            'nama_user'          => 'required|string|max:255',
            'email_user'         => 'required|string|email|max:255|unique:users,email_user',
            'password_user'      => 'required|string|min:6|confirmed',
            'nohp_user'          => 'nullable|string|regex:/^[0-9]+$/|min:10|max:15',
            'dokumen_pendukung'  => 'nullable|string|max:500',
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
            'nama_user'          => $request->nama_user,
            'email_user'         => $request->email_user,
            'password_user'      => $request->password_user,
            'role'               => 'user',
            'nohp_user'          => $request->nohp_user,
            'id_pengelola'       => $request->id_pengelola ?? Auth::id(),
            'dokumen_pendukung'  => $request->dokumen_pendukung,
            'email_verified_at'  => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User account created successfully',
            'data'    => $user,
            'errors'  => null,
        ], 201);
    }

    /**
     * Decode and save base64 KTP image to disk, rewriting request field with path.
     */
    private function handleBase64Ktp(Request $request): void
    {
        if ($request->filled('dokumen_pendukung') && str_starts_with($request->input('dokumen_pendukung'), 'data:image')) {
            try {
                $base64 = $request->input('dokumen_pendukung');
                if (preg_match('/^data:image\/(\w+);base64,/', $base64, $typeMatches)) {
                    $imageType = strtolower($typeMatches[1]);
                    $data = substr($base64, strpos($base64, ',') + 1);
                    $data = base64_decode($data);
                    
                    if ($data !== false) {
                        $filename = 'ktp_' . uniqid() . '.' . $imageType;
                        $directoryPath = public_path('uploads/ktp');
                        
                        if (!file_exists($directoryPath)) {
                            mkdir($directoryPath, 0777, true);
                        }
                        
                        file_put_contents($directoryPath . '/' . $filename, $data);
                        
                        $request->merge([
                            'dokumen_pendukung' => '/uploads/ktp/' . $filename
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Ignore failure and let default behavior handle validation
            }
        }
    }
}
