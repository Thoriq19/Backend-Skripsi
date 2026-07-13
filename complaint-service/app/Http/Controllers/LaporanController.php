<?php

namespace App\Http\Controllers;

use App\Models\LaporanKerusakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LaporanController extends Controller
{
    /**
     * Get all laporan kerusakan.
     *
     * GET /api/laporan
     */
    public function index(Request $request): JsonResponse
    {
        $query = LaporanKerusakan::query();

        // Filter by user
        if ($request->has('id_user')) {
            $query->where('id_user', $request->id_user);
        }

        // Filter by aset
        if ($request->has('id_aset')) {
            $query->where('id_aset', $request->id_aset);
        }

        // Filter by status
        if ($request->has('status_laporan')) {
            $query->where('status_laporan', $request->status_laporan);
        }

        $perPage = $request->get('per_page', 15);
        $laporan = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Laporan retrieved successfully',
            'data'    => $laporan,
            'errors'  => null,
        ]);
    }

    /**
     * Create a new laporan kerusakan.
     *
     * POST /api/laporan
     *
     * Setelah laporan dibuat, sistem otomatis mengirim notifikasi
     * ke pengelola kos melalui Notification Service (Prosedur 7c).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'deskripsi'      => 'required|string',
            'foto'           => 'nullable|string|max:500',
            'status_laporan' => 'sometimes|in:dilaporkan,diproses,selesai',
            'id_user'        => 'required|integer|exists:users,id',
            'id_aset'        => 'required|integer|exists:aset,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $laporan = LaporanKerusakan::create(array_merge(
            $request->all(),
            ['tanggal_lapor' => now()]
        ));

        // Kirim notifikasi otomatis ke pengelola kos (Prosedur 7c)
        try {
            $notifClient = new \Shared\MicroserviceClient(
                env('NOTIFICATION_SERVICE_URL', 'http://localhost:8007')
            );

            // Notifikasi dikirim ke pengelola kos (id_user dari request header jika tersedia)
            $notifClient->post('/api/notifikasi', [
                'id_user'      => $request->id_user,
                'judul'        => 'Laporan Kerusakan Baru',
                'pesan'        => "Laporan kerusakan baru telah dibuat: {$request->deskripsi}",
                'tipe'         => 'laporan',
                'id_terkait'   => $laporan->id,
                'tipe_terkait' => 'laporan_kerusakan',
            ]);
        } catch (\Exception $e) {
            // Notifikasi gagal tidak menghentikan proses pembuatan laporan
        }

        return response()->json([
            'success' => true,
            'message' => 'Laporan created successfully',
            'data'    => $laporan,
            'errors'  => null,
        ], 201);
    }

    /**
     * Get a specific laporan kerusakan.
     *
     * GET /api/laporan/{id}
     */
    public function show(int $id): JsonResponse
    {
        $laporan = LaporanKerusakan::find($id);

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Laporan retrieved successfully',
            'data'    => $laporan,
            'errors'  => null,
        ]);
    }

    /**
     * Update a laporan kerusakan.
     *
     * PUT /api/laporan/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $laporan = LaporanKerusakan::find($id);

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'deskripsi'      => 'sometimes|string',
            'foto'           => 'nullable|string|max:500',
            'status_laporan' => 'sometimes|in:dilaporkan,diproses,selesai',
            'id_user'        => 'sometimes|integer|exists:users,id',
            'id_aset'        => 'sometimes|integer|exists:aset,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $laporan->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Laporan updated successfully',
            'data'    => $laporan->fresh(),
            'errors'  => null,
        ]);
    }

    /**
     * Update status of a laporan kerusakan.
     *
     * PUT /api/laporan/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $laporan = LaporanKerusakan::find($id);

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status_laporan' => 'required|in:dilaporkan,diproses,selesai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $laporan->update(['status_laporan' => $request->status_laporan]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan status updated successfully',
            'data'    => $laporan->fresh(),
            'errors'  => null,
        ]);
    }

    /**
     * Delete a laporan kerusakan (soft delete).
     *
     * DELETE /api/laporan/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $laporan = LaporanKerusakan::find($id);

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan not found',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $laporan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Laporan deleted successfully',
            'data'    => null,
            'errors'  => null,
        ]);
    }
}
