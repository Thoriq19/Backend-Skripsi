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

        $perPage = $request->get('per_page', 15);
        $kos = $query->with('kamar')->paginate($perPage);

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
        $validator = Validator::make($request->all(), [
            'nama_kos'   => 'required|string|max:255',
            'alamat_kos' => 'required|string',
            'id_user'    => 'required|integer|exists:users,id',
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
        $kos = Kos::with(['kamar', 'aset'])->find($id);

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
            'nama_kos'   => 'sometimes|string|max:255',
            'alamat_kos' => 'sometimes|string',
            'id_user'    => 'sometimes|integer|exists:users,id',
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
    public function destroy(int $id): JsonResponse
    {
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
