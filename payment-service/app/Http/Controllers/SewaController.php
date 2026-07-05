<?php

namespace App\Http\Controllers;

use App\Models\Sewa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SewaController extends Controller
{
    /**
     * Get all sewa records.
     *
     * GET /api/sewa
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sewa::query();

        // Filter by user
        if ($request->has('id_user')) {
            $query->where('id_user', $request->id_user);
        }

        // Filter by kamar
        if ($request->has('id_kamar')) {
            $query->where('id_kamar', $request->id_kamar);
        }

        // Filter by status
        if ($request->has('status_sewa')) {
            $query->where('status_sewa', $request->status_sewa);
        }

        $perPage = $request->get('per_page', 15);
        $sewa = $query->with('tagihan')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Sewa retrieved successfully',
            'data'    => $sewa,
            'errors'  => null,
        ]);
    }

    /**
     * Create a new sewa.
     *
     * POST /api/sewa
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tanggal_masuk'  => 'required|date',
            'tanggal_keluar' => 'required|date|after:tanggal_masuk',
            'status_sewa'    => 'sometimes|in:aktif,berakhir,dibatalkan',
            'id_user'        => 'required|integer|exists:users,id',
            'id_kamar'       => 'required|integer|exists:kamar,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $sewa = Sewa::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Sewa created successfully',
            'data'    => $sewa,
            'errors'  => null,
        ], 201);
    }

    /**
     * Get a specific sewa.
     *
     * GET /api/sewa/{id}
     */
    public function show(int $id): JsonResponse
    {
        $sewa = Sewa::with('tagihan')->find($id);

        if (!$sewa) {
            return response()->json([
                'success' => false,
                'message' => 'Sewa not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sewa retrieved successfully',
            'data'    => $sewa,
            'errors'  => null,
        ]);
    }

    /**
     * Update a sewa.
     *
     * PUT /api/sewa/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $sewa = Sewa::find($id);

        if (!$sewa) {
            return response()->json([
                'success' => false,
                'message' => 'Sewa not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'tanggal_masuk'  => 'sometimes|date',
            'tanggal_keluar' => 'sometimes|date|after:tanggal_masuk',
            'status_sewa'    => 'sometimes|in:aktif,berakhir,dibatalkan',
            'id_user'        => 'sometimes|integer|exists:users,id',
            'id_kamar'       => 'sometimes|integer|exists:kamar,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $sewa->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Sewa updated successfully',
            'data'    => $sewa->fresh()->load('tagihan'),
            'errors'  => null,
        ]);
    }

    /**
     * Delete a sewa (soft delete).
     *
     * DELETE /api/sewa/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $sewa = Sewa::find($id);

        if (!$sewa) {
            return response()->json([
                'success' => false,
                'message' => 'Sewa not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $sewa->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sewa deleted successfully',
            'data'    => null,
            'errors'  => null,
        ]);
    }
}
