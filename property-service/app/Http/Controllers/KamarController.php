<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KamarController extends Controller
{
    /**
     * Get all kamar with optional filtering.
     *
     * GET /api/kamar
     */
    public function index(Request $request): JsonResponse
    {
        $query = Kamar::query();

        // Filter by status
        if ($request->has('status_kamar')) {
            $query->where('status_kamar', $request->status_kamar);
        }

        // Filter by kos
        if ($request->has('id_kos')) {
            $query->where('id_kos', $request->id_kos);
        }

        // Filter by max price
        if ($request->has('max_harga')) {
            $query->where('harga_kamar', '<=', $request->max_harga);
        }

        $perPage = $request->get('per_page', 15);
        $kamar = $query->with('kos')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Kamar retrieved successfully',
            'data'    => $kamar,
            'errors'  => null,
        ]);
    }

    /**
     * Create a new kamar.
     *
     * POST /api/kamar
     */
    public function store(Request $request): JsonResponse
    {
        // Authorize: Only owner can add new physical rooms to a property
        $userRole = $request->input('auth_user_role');
        if ($userRole !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only owner can add rooms to a property.',
                'data'    => null,
                'errors'  => null,
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nomor_kamar'     => 'required|string|max:50',
            'tipe_kamar'      => 'nullable|string|max:100',
            'kapasitas_kamar' => 'required|integer|min:1',
            'harga_kamar'     => 'required|numeric|min:0',
            'status_kamar'    => 'sometimes|in:tersedia,terisi,maintenance,tidak_tersedia,segera',
            'deskripsi_kamar' => 'nullable|string',
            'id_kos'          => 'required|integer|exists:kos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $kamar = Kamar::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kamar created successfully',
            'data'    => $kamar,
            'errors'  => null,
        ], 201);
    }

    /**
     * Get a specific kamar.
     *
     * GET /api/kamar/{id}
     */
    public function show(int $id): JsonResponse
    {
        $kamar = Kamar::with('kos')->find($id);

        if (!$kamar) {
            return response()->json([
                'success' => false,
                'message' => 'Kamar not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kamar retrieved successfully',
            'data'    => $kamar,
            'errors'  => null,
        ]);
    }

    /**
     * Update a kamar.
     *
     * PUT /api/kamar/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Authorize: Both owner and pengelola_kos can update room details
        $userRole = $request->input('auth_user_role');
        if (!in_array($userRole, ['owner', 'pengelola_kos'])) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only owner or pengelola kos can update room details.',
                'data'    => null,
                'errors'  => null,
            ], 403);
        }

        $kamar = Kamar::find($id);

        if (!$kamar) {
            return response()->json([
                'success' => false,
                'message' => 'Kamar not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nomor_kamar'     => 'sometimes|string|max:50',
            'tipe_kamar'      => 'nullable|string|max:100',
            'kapasitas_kamar' => 'sometimes|integer|min:1',
            'harga_kamar'     => 'sometimes|numeric|min:0',
            'status_kamar'    => 'sometimes|in:tersedia,terisi,maintenance,tidak_tersedia,segera',
            'deskripsi_kamar' => 'nullable|string',
            'id_kos'          => 'sometimes|integer|exists:kos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $kamar->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Kamar updated successfully',
            'data'    => $kamar->fresh()->load('kos'),
            'errors'  => null,
        ]);
    }

    /**
     * Delete a kamar (soft delete).
     *
     * DELETE /api/kamar/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        // Authorize: Only owner can delete physical rooms (assets)
        $userRole = request()->input('auth_user_role');
        if ($userRole !== 'owner') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only owner can delete physical rooms.',
                'data'    => null,
                'errors'  => null,
            ], 403);
        }

        $kamar = Kamar::find($id);

        if (!$kamar) {
            return response()->json([
                'success' => false,
                'message' => 'Kamar not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $kamar->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kamar deleted successfully',
            'data'    => null,
            'errors'  => null,
        ]);
    }
}
