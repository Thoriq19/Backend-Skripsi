<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Shared\MicroserviceClient;

class TagihanController extends Controller
{
    /**
     * Helper to automatically sync overdue bills and send tri-actor notifications.
     */
    private function syncOverdueStatus(): void
    {
        try {
            $today = date('Y-m-d');
            $overdueBills = Tagihan::where('status_tagihan', 'belum_bayar')
                ->where('tanggal_jatuhtempo', '<', $today)
                ->get();

            if ($overdueBills->isEmpty()) {
                return;
            }

            $notifClient = new MicroserviceClient(
                env('NOTIFICATION_SERVICE_URL', 'http://localhost:8007')
            );

            foreach ($overdueBills as $item) {
                // Update status in DB
                $item->update(['status_tagihan' => 'terlambat']);

                // Retrieve sewa, kamar, and kos info for notification dispatch
                $sewa = DB::table('sewa')->where('id', $item->id_sewa)->first();
                if ($sewa) {
                    $kamar = DB::table('kamar')->where('id', $sewa->id_kamar)->first();
                    $kos = $kamar ? DB::table('kos')->where('id', $kamar->id_kos)->first() : null;

                    $formattedAmount = "Rp " . number_format($item->jumlah_tagihan, 0, ',', '.');
                    $formattedDueDate = date('d/m/Y', strtotime($item->tanggal_jatuhtempo));

                    // 1. Send Notification to Tenant (Penghuni)
                    try {
                        $notifClient->post('/api/notifikasi', [
                            'id_user'      => $sewa->id_user,
                            'judul'        => '⚠️ Tagihan Lewat Jatuh Tempo',
                            'pesan'        => "Tagihan sewa bulan {$item->bulan_tagihan} sebesar {$formattedAmount} telah melewati jatuh tempo ({$formattedDueDate}). Harap segera melakukan pembayaran.",
                            'tipe'         => 'peringatan',
                            'id_terkait'   => $item->id,
                            'tipe_terkait' => 'tagihan',
                        ]);
                    } catch (\Exception $e) {}

                    // 2. Send Notification to Pengelola Kos (Manager)
                    if ($kos && !empty($kos->id_pengelola)) {
                        try {
                            $notifClient->post('/api/notifikasi', [
                                'id_user'      => $kos->id_pengelola,
                                'judul'        => '⚠️ Peringatan Keterlambatan Sewa',
                                'pesan'        => "Penghuni Kamar {$kamar->nomor_kamar} di Kos '{$kos->nama_kos}' belum membayar tagihan {$formattedAmount} yang telah lewat jatuh tempo ({$formattedDueDate}).",
                                'tipe'         => 'peringatan',
                                'id_terkait'   => $item->id,
                                'tipe_terkait' => 'tagihan',
                            ]);
                        } catch (\Exception $e) {}
                    }

                    // 3. Send Notification to Owner (Pemilik Kos)
                    if ($kos && !empty($kos->id_user)) {
                        try {
                            $notifClient->post('/api/notifikasi', [
                                'id_user'      => $kos->id_user,
                                'judul'        => '📌 Laporan Tunggakan Sewa',
                                'pesan'        => "Terdapat 1 tagihan sewa terlambat sebesar {$formattedAmount} (Kamar {$kamar->nomor_kamar}) di Kos '{$kos->nama_kos}'.",
                                'tipe'         => 'peringatan',
                                'id_terkait'   => $item->id,
                                'tipe_terkait' => 'tagihan',
                            ]);
                        } catch (\Exception $e) {}
                    }
                }
            }
        } catch (\Exception $e) {
            // Silence exception to avoid breaking list requests
        }
    }

    /**
     * Get all tagihan.
     *
     * GET /api/tagihan
     */
    public function index(Request $request): JsonResponse
    {
        $this->syncOverdueStatus();

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
        $tagihan = $query->with(['sewa.user', 'pembayaran'])->paginate($perPage);

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
        $this->syncOverdueStatus();
        $tagihan = Tagihan::with(['sewa.user', 'pembayaran'])->find($id);

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
