<?php

namespace App\Http\Controllers;

use App\Models\Kos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KosController extends Controller
{
    /**
     * Get all kos with optional filtering.
     *
     * GET /api/kos
     */
    public function index(Request $request): JsonResponse
    {
        $query = Kos::query();

        // Filter by owner
        if ($request->has('id_user')) {
            $query->where('id_user', $request->id_user);
        }

        // Filter by pengelola
        if ($request->has('id_pengelola')) {
            $query->where('id_pengelola', $request->id_pengelola);
        }

        $perPage = $request->get('per_page', 15);
        $kos = $query->with(['kamar', 'pengelola'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Kos retrieved successfully',
            'data'    => $kos,
            'errors'  => null,
        ]);
    }

    /**
     * Create a new kos.
     *
     * POST /api/kos
     */
    public function store(Request $request): JsonResponse
    {
        // Authorize: Only owner can create kos data
        $userRole = $request->input('auth_user_role');
        if ($userRole !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only owners can create kos data.',
                'data'    => null,
                'errors'  => null,
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_kos'      => 'required|string|max:255',
            'alamat_kos'    => 'required|string',
            'jumlah_kamar'  => 'required|integer|min:0',
            'id_user'       => 'required|integer|exists:users,id',
            'id_pengelola'  => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $kos = Kos::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kos created successfully',
            'data'    => $kos,
            'errors'  => null,
        ], 201);
    }

    /**
     * Get a specific kos with its kamar and aset.
     *
     * GET /api/kos/{id}
     */
    public function show(int $id): JsonResponse
    {
        $kos = Kos::with(['kamar', 'aset', 'pengelola'])->find($id);

        if (!$kos) {
            return response()->json([
                'success' => false,
                'message' => 'Kos not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kos retrieved successfully',
            'data'    => $kos,
            'errors'  => null,
        ]);
    }

    /**
     * Update a kos.
     *
     * PUT /api/kos/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Authorize: Only owner can update kos data
        $userRole = $request->input('auth_user_role');
        if ($userRole !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only owners can update kos data.',
                'data'    => null,
                'errors'  => null,
            ], 403);
        }

        $kos = Kos::find($id);

        if (!$kos) {
            return response()->json([
                'success' => false,
                'message' => 'Kos not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_kos'      => 'sometimes|string|max:255',
            'alamat_kos'    => 'sometimes|string',
            'jumlah_kamar'  => 'sometimes|integer|min:0',
            'id_user'       => 'sometimes|integer|exists:users,id',
            'id_pengelola'  => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $kos->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kos updated successfully',
            'data'    => $kos->fresh()->load(['kamar', 'aset']),
            'errors'  => null,
        ]);
    }

    /**
     * Delete a kos (soft delete).
     *
     * DELETE /api/kos/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        // Authorize: Only owner can delete kos data
        $userRole = $request->input('auth_user_role');
        if ($userRole !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only owners can delete kos data.',
                'data'    => null,
                'errors'  => null,
            ], 403);
        }

        $kos = Kos::find($id);

        if (!$kos) {
            return response()->json([
                'success' => false,
                'message' => 'Kos not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $kos->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kos deleted successfully',
            'data'    => null,
            'errors'  => null,
        ]);
    }
}
