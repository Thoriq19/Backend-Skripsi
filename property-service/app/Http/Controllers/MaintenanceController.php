<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaintenanceController extends Controller
{
    /**
     * Get all maintenance records.
     *
     * GET /api/maintenance
     */
    public function index(Request $request): JsonResponse
    {
        $query = Maintenance::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by aset
        if ($request->has('id_aset')) {
            $query->where('id_aset', $request->id_aset);
        }

        $perPage = $request->get('per_page', 15);
        $maintenance = $query->with('aset')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance retrieved successfully',
            'data'    => $maintenance,
            'errors'  => null,
        ]);
    }

    /**
     * Create a new maintenance record.
     *
     * POST /api/maintenance
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'deskripsi'          => 'required|string',
            'biaya'              => 'required|numeric|min:0',
            'tanggal_perbaikan'  => 'required|date',
            'status'             => 'sometimes|in:dijadwalkan,sedang_dikerjakan,selesai',
            'id_aset'            => 'required|integer|exists:aset,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $maintenance = Maintenance::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Maintenance created successfully',
            'data'    => $maintenance,
            'errors'  => null,
        ], 201);
    }

    /**
     * Get a specific maintenance record.
     *
     * GET /api/maintenance/{id}
     */
    public function show(int $id): JsonResponse
    {
        $maintenance = Maintenance::with('aset')->find($id);

        if (!$maintenance) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Maintenance retrieved successfully',
            'data'    => $maintenance,
            'errors'  => null,
        ]);
    }

    /**
     * Update a maintenance record.
     *
     * PUT /api/maintenance/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'deskripsi'          => 'sometimes|string',
            'biaya'              => 'sometimes|numeric|min:0',
            'tanggal_perbaikan'  => 'sometimes|date',
            'status'             => 'sometimes|in:dijadwalkan,sedang_dikerjakan,selesai',
            'id_aset'            => 'sometimes|integer|exists:aset,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $maintenance->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Maintenance updated successfully',
            'data'    => $maintenance->fresh()->load('aset'),
            'errors'  => null,
        ]);
    }

    /**
     * Delete a maintenance record.
     *
     * DELETE /api/maintenance/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $maintenance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance deleted successfully',
            'data'    => null,
            'errors'  => null,
        ]);
    }
}
