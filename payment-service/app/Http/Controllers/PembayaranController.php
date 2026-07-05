<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PembayaranController extends Controller
{
    /**
     * Get all pembayaran.
     *
     * GET /api/pembayaran
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pembayaran::query();

        // Filter by tagihan
        if ($request->has('id_tagihan')) {
            $query->where('id_tagihan', $request->id_tagihan);
        }

        // Filter by status
        if ($request->has('status_pembayaran')) {
            $query->where('status_pembayaran', $request->status_pembayaran);
        }

        $perPage = $request->get('per_page', 15);
        $pembayaran = $query->with('tagihan')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran retrieved successfully',
            'data'    => $pembayaran,
            'errors'  => null,
        ]);
    }

    /**
     * Create a new pembayaran.
     *
     * POST /api/pembayaran
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tanggal_bayar'      => 'nullable|date',
            'metode_pembayaran'  => 'required|in:transfer_bank,e_wallet,tunai',
            'jumlah_bayar'       => 'required|numeric|min:0',
            'status_pembayaran'  => 'sometimes|in:pending,berhasil,gagal',
            'payment_gateway'    => 'sometimes|in:manual,xendit,midtrans',
            'external_id'        => 'nullable|string|unique:pembayaran,external_id',
            'id_tagihan'         => 'required|integer|exists:tagihan,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $pembayaran = Pembayaran::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran created successfully',
            'data'    => $pembayaran,
            'errors'  => null,
        ], 201);
    }

    /**
     * Get a specific pembayaran.
     *
     * GET /api/pembayaran/{id}
     */
    public function show(int $id): JsonResponse
    {
        $pembayaran = Pembayaran::with('tagihan')->find($id);

        if (!$pembayaran) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran retrieved successfully',
            'data'    => $pembayaran,
            'errors'  => null,
        ]);
    }

    /**
     * Update pembayaran status.
     *
     * PUT /api/pembayaran/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $pembayaran = Pembayaran::find($id);

        if (!$pembayaran) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status_pembayaran' => 'required|in:pending,berhasil,gagal',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $pembayaran->update([
            'status_pembayaran' => $request->status_pembayaran,
            'tanggal_bayar'     => $request->status_pembayaran === 'berhasil' ? now() : $pembayaran->tanggal_bayar,
        ]);

        // If payment successful, update tagihan status to lunas
        if ($request->status_pembayaran === 'berhasil') {
            $pembayaran->tagihan->update(['status_tagihan' => 'lunas']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran status updated successfully',
            'data'    => $pembayaran->fresh()->load('tagihan'),
            'errors'  => null,
        ]);
    }

    /**
     * Handle payment gateway webhook callback.
     * This endpoint is PUBLIC (no JWT required).
     *
     * POST /api/pembayaran/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'external_id' => 'required|string',
            'status'      => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $pembayaran = Pembayaran::where('external_id', $request->external_id)->first();

        if (!$pembayaran) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran not found for this external_id',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        // Map gateway status to our status
        $statusMap = [
            'PAID'      => 'berhasil',
            'SETTLED'   => 'berhasil',
            'EXPIRED'   => 'gagal',
            'FAILED'    => 'gagal',
        ];

        $newStatus = $statusMap[strtoupper($request->status)] ?? 'pending';

        $pembayaran->update([
            'status_pembayaran' => $newStatus,
            'status_webhook'    => 'received',
            'tanggal_bayar'     => $newStatus === 'berhasil' ? now() : $pembayaran->tanggal_bayar,
        ]);

        // If payment successful, update tagihan status
        if ($newStatus === 'berhasil') {
            $pembayaran->tagihan->update(['status_tagihan' => 'lunas']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed successfully',
            'data'    => $pembayaran->fresh(),
            'errors'  => null,
        ]);
    }
}
