<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagihanController extends Controller
{
    /**
     * Get all tagihan.
     *
     * GET /api/tagihan
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tagihan::query();

        // Filter by sewa
        if ($request->has('id_sewa')) {
            $query->where('id_sewa', $request->id_sewa);
        }

        // Filter by status
        if ($request->has('status_tagihan')) {
            $query->where('status_tagihan', $request->status_tagihan);
        }

        $perPage = $request->get('per_page', 15);
        $tagihan = $query->with(['sewa', 'pembayaran'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Tagihan retrieved successfully',
            'data'    => $tagihan,
            'errors'  => null,
        ]);
    }

    /**
     * Create a new tagihan.
     *
     * POST /api/tagihan
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bulan_tagihan'      => 'required|string|max:7',
            'tanggal_jatuhtempo' => 'required|date',
            'jumlah_tagihan'     => 'required|numeric|min:0',
            'status_tagihan'     => 'sometimes|in:belum_bayar,lunas,terlambat',
            'id_sewa'            => 'required|integer|exists:sewa,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $tagihan = Tagihan::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tagihan created successfully',
            'data'    => $tagihan,
            'errors'  => null,
        ], 201);
    }

    /**
     * Get a specific tagihan.
     *
     * GET /api/tagihan/{id}
     */
    public function show(int $id): JsonResponse
    {
        $tagihan = Tagihan::with(['sewa', 'pembayaran'])->find($id);

        if (!$tagihan) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tagihan retrieved successfully',
            'data'    => $tagihan,
            'errors'  => null,
        ]);
    }

    /**
     * Update a tagihan.
     *
     * PUT /api/tagihan/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tagihan = Tagihan::find($id);

        if (!$tagihan) {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'bulan_tagihan'      => 'sometimes|string|max:7',
            'tanggal_jatuhtempo' => 'sometimes|date',
            'jumlah_tagihan'     => 'sometimes|numeric|min:0',
            'status_tagihan'     => 'sometimes|in:belum_bayar,lunas,terlambat',
            'id_sewa'            => 'sometimes|integer|exists:sewa,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $tagihan->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Tagihan updated successfully',
            'data'    => $tagihan->fresh(),
            'errors'  => null,
        ]);
    }
}
