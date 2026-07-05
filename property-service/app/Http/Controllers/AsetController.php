<?php

namespace App\Http\Controllers;

use App\Models\Aset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AsetController extends Controller
{
    /**
     * Get all aset with optional filtering.
     *
     * GET /api/aset
     */
    public function index(Request $request): JsonResponse
    {
        $query = Aset::query();

        // Filter by kondisi
        if ($request->has('kondisi')) {
            $query->where('kondisi', $request->kondisi);
        }

        // Filter by kos
        if ($request->has('id_kos')) {
            $query->where('id_kos', $request->id_kos);
        }

        $perPage = $request->get('per_page', 15);
        $aset = $query->with('kos')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Aset retrieved successfully',
            'data'    => $aset,
            'errors'  => null,
        ]);
    }

    /**
     * Create a new aset.
     *
     * POST /api/aset
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_aset'          => 'required|string|max:255',
            'tanggal_pembelian'  => 'required|date',
            'harga'              => 'required|numeric|min:0',
            'kondisi'            => 'sometimes|in:baik,rusak_ringan,rusak_berat',
            'id_kos'             => 'required|integer|exists:kos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $aset = Aset::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Aset created successfully',
            'data'    => $aset,
            'errors'  => null,
        ], 201);
    }

    /**
     * Get a specific aset.
     *
     * GET /api/aset/{id}
     */
    public function show(int $id): JsonResponse
    {
        $aset = Aset::with(['kos', 'maintenance'])->find($id);

        if (!$aset) {
            return response()->json([
                'success' => false,
                'message' => 'Aset not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Aset retrieved successfully',
            'data'    => $aset,
            'errors'  => null,
        ]);
    }

    /**
     * Update an aset.
     *
     * PUT /api/aset/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $aset = Aset::find($id);

        if (!$aset) {
            return response()->json([
                'success' => false,
                'message' => 'Aset not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_aset'          => 'sometimes|string|max:255',
            'tanggal_pembelian'  => 'sometimes|date',
            'harga'              => 'sometimes|numeric|min:0',
            'kondisi'            => 'sometimes|in:baik,rusak_ringan,rusak_berat',
            'id_kos'             => 'sometimes|integer|exists:kos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $aset->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Aset updated successfully',
            'data'    => $aset->fresh(),
            'errors'  => null,
        ]);
    }

    /**
     * Delete an aset (soft delete).
     *
     * DELETE /api/aset/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $aset = Aset::find($id);

        if (!$aset) {
            return response()->json([
                'success' => false,
                'message' => 'Aset not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $aset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Aset deleted successfully',
            'data'    => null,
            'errors'  => null,
        ]);
    }
}
