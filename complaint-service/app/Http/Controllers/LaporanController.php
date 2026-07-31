<?php

namespace App\Http\Controllers;

use App\Models\LaporanKerusakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
        $rules = [
            'deskripsi'      => 'required|string',
            'status_laporan' => 'sometimes|in:dilaporkan,diproses,selesai',
            'id_user'        => 'required|integer|exists:users,id',
            'id_aset'        => 'required|integer|exists:aset,id',
        ];

        if ($request->hasFile('foto_laporan')) {
            $rules['foto_laporan'] = 'file|mimes:jpeg,png,jpg,webp,svg,gif,jfif,heic|max:10240';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $fotoUrl = null;
        if ($request->hasFile('foto_laporan')) {
            $file = $request->file('foto_laporan');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('laporan', $filename, 'public');
            $fotoUrl = '/storage/laporan/' . $filename;
        }

        $laporan = LaporanKerusakan::create([
            'deskripsi'      => $request->deskripsi,
            'status_laporan' => $request->status_laporan ?? 'dilaporkan',
            'id_user'        => $request->id_user,
            'id_aset'        => $request->id_aset,
            'foto_laporan'   => $fotoUrl,
            'tanggal_lapor'  => now(),
        ]);

        // Kirim notifikasi otomatis ke Pengelola, Owner, dan Penghuni (Prosedur 7c)
        try {
            $notifClient = new \Shared\MicroserviceClient(
                env('NOTIFICATION_SERVICE_URL', 'http://localhost:8007')
            );

            // 1. Kirim notifikasi konfirmasi ke Penghuni (tenant)
            $notifClient->post('/api/notifikasi', [
                'id_user'      => $request->id_user,
                'judul'        => 'Laporan Kerusakan Dikirim',
                'pesan'        => "Laporan Anda mengenai kerusakan fasilitas telah berhasil dikirim: {$request->deskripsi}",
                'tipe'         => 'laporan',
                'id_terkait'   => $laporan->id,
                'tipe_terkait' => 'laporan_kerusakan',
            ]);

            // Cari Kos dan Owner/Pengelola berdasarkan id_aset
            $aset = DB::table('aset')->where('id', $laporan->id_aset)->first();
            if ($aset) {
                $kos = DB::table('kos')->where('id', $aset->id_kos)->first();
                if ($kos) {
                    // 2. Kirim ke Pengelola Kos (jika ada)
                    if (!empty($kos->id_pengelola)) {
                        $notifClient->post('/api/notifikasi', [
                            'id_user'      => $kos->id_pengelola,
                            'judul'        => 'Laporan Kerusakan Baru',
                            'pesan'        => "Ada laporan kerusakan baru pada aset '{$aset->nama_aset}' di kos '{$kos->nama_kos}': {$request->deskripsi}",
                            'tipe'         => 'laporan',
                            'id_terkait'   => $laporan->id,
                            'tipe_terkait' => 'laporan_kerusakan',
                        ]);
                    }

                }
            }
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

        // Kirim notifikasi otomatis ke Penghuni bahwa status laporan berubah (Prosedur 7c)
        try {
            $notifClient = new \Shared\MicroserviceClient(
                env('NOTIFICATION_SERVICE_URL', 'http://localhost:8007')
            );
            
            // Cari nama aset untuk detail pesan
            $asetName = 'Fasilitas';
            $aset = DB::table('aset')->where('id', $laporan->id_aset)->first();
            if ($aset) {
                $asetName = $aset->nama_aset;
            }

            $notifClient->post('/api/notifikasi', [
                'id_user'      => $laporan->id_user,
                'judul'        => 'Pembaruan Status Aduan',
                'pesan'        => "Laporan kerusakan Anda terkait '{$asetName}' telah diperbarui menjadi " . strtoupper($request->status_laporan) . ".",
                'tipe'         => 'laporan',
                'id_terkait'   => $laporan->id,
                'tipe_terkait' => 'laporan_kerusakan',
            ]);
        } catch (\Exception $e) {
            //
        }

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
