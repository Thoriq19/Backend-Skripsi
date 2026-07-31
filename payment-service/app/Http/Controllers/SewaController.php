<?php

namespace App\Http\Controllers;

use App\Models\Sewa;
use App\Models\Tagihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Shared\MicroserviceClient;

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
     *
     * Setelah sewa dibuat, sistem otomatis men-generate tagihan pertama
     * berdasarkan tanggal masuk penghuni (Prosedur 4d).
     */
    public function store(Request $request): JsonResponse
    {
        // Authorize: Only owner or pengelola_kos can create sewa
        $userRole = $request->input('auth_user_role');
        if (!in_array($userRole, ['owner', 'pengelola_kos'])) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only owner and pengelola kos can create sewa contracts.',
                'data'    => null,
                'errors'  => null,
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'tanggal_masuk'  => 'required|date',
            'tanggal_keluar' => 'nullable|date|after:tanggal_masuk',
            'status_sewa'    => 'sometimes|in:aktif,berakhir,dibatalkan',
            'harga_sewa'     => 'required|numeric|min:0',
            'deposit'        => 'nullable|numeric|min:0',
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

        $sewa = Sewa::create($request->except(['deposit']));

        // Auto-generate tagihan pertama berdasarkan tanggal masuk + deposit Rp 200.000 (Prosedur 4d)
        $deposit = $request->has('deposit') ? (float) $request->deposit : 200000;
        $totalTagihanPertama = (float) $request->harga_sewa + $deposit;

        $tanggalMasuk = \Carbon\Carbon::parse($request->tanggal_masuk);
        $tagihan = Tagihan::create([
            'bulan_tagihan'      => $tanggalMasuk->format('Y-m'),
            'tanggal_jatuhtempo' => $tanggalMasuk->toDateString(),
            'jumlah_tagihan'     => $totalTagihanPertama,
            'status_tagihan'     => 'belum_bayar',
            'id_sewa'            => $sewa->id,
        ]);

        // Dispatch initial bill notification to tenant
        try {
            $notifClient = new MicroserviceClient(
                env('NOTIFICATION_SERVICE_URL', 'http://localhost:8007')
            );
            $formattedTotal = "Rp " . number_format($totalTagihanPertama, 0, ',', '.');
            $notifClient->post('/api/notifikasi', [
                'id_user'      => $sewa->id_user,
                'judul'        => '📋 Kontrak Sewa & Tagihan Pertama',
                'pesan'        => "Selamat! Kontrak sewa Anda telah aktif. Silakan lakukan pembayaran tagihan pertama (Sewa + Deposit Rp 200.000) sebesar {$formattedTotal} sebagai syarat masuk.",
                'tipe'         => 'pembayaran',
                'id_terkait'   => $tagihan->id,
                'tipe_terkait' => 'tagihan',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Initial bill notification failed: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Sewa created successfully. Tagihan pertama otomatis dibuat.',
            'data'    => $sewa->load('tagihan'),
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
        // Authorize: Only owner or pengelola_kos can update sewa
        $userRole = $request->input('auth_user_role');
        if (!in_array($userRole, ['owner', 'pengelola_kos'])) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only owner and pengelola kos can update sewa contracts.',
                'data'    => null,
                'errors'  => null,
            ], 403);
        }

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
            'tanggal_keluar' => 'nullable|date|after:tanggal_masuk',
            'status_sewa'    => 'sometimes|in:aktif,berakhir,dibatalkan',
            'harga_sewa'     => 'sometimes|numeric|min:0',
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
    public function destroy(Request $request, int $id): JsonResponse
    {
        // Authorize: Only owner or pengelola_kos can delete sewa
        $userRole = $request->input('auth_user_role');
        if (!in_array($userRole, ['owner', 'pengelola_kos'])) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only owner and pengelola kos can delete sewa contracts.',
                'data'    => null,
                'errors'  => null,
            ], 403);
        }

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
