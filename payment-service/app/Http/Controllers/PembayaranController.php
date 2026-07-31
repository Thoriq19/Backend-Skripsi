<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

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
            'metode_pembayaran'  => 'required|in:transfer_bank,e_wallet',
            'jumlah_bayar'       => 'required|numeric|min:0',
            'status_pembayaran'  => 'sometimes|in:pending,berhasil,gagal',
            'payment_gateway'    => 'sometimes|in:xendit,midtrans',
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
     * Create Midtrans Snap Token for a Tagihan.
     *
     * POST /api/pembayaran/snap
     */
    public function createSnapToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id_tagihan' => 'required|integer|exists:tagihan,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $tagihan = \App\Models\Tagihan::find($request->id_tagihan);
        
        if ($tagihan->status_tagihan === 'lunas') {
            return response()->json([
                'success' => false,
                'message' => 'Tagihan ini sudah lunas.',
                'data'    => null,
                'errors'  => null,
            ], 400);
        }

        // Cancel / expire previous pending payment attempts so Midtrans displays fresh payment selection screen
        Pembayaran::where('id_tagihan', $tagihan->id)
            ->where('status_pembayaran', 'pending')
            ->update(['status_pembayaran' => 'gagal']);

        $sewa = $tagihan->sewa;
        $user = $sewa ? DB::table('users')->find($sewa->id_user) : null;
        $kamar = $sewa ? DB::table('kamar')->find($sewa->id_kamar) : null;

        $orderId = 'TRX-' . $tagihan->id . '-' . uniqid();
        $amount = (int) $tagihan->jumlah_tagihan;

        $serverKey = 'Mid-server-23qND_PPA2PPkuN84ntlA_At';
        $isProduction = false;
        
        $snapToken = null;

        if (empty($serverKey) || str_contains($serverKey, 'xxxx') || str_contains($serverKey, 'dummy')) {
            $snapToken = 'dummy-snap-token-' . uniqid();
        } else {
            try {
                $endpoint = $isProduction 
                    ? 'https://app.midtrans.com/snap/v1/transactions' 
                    : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

                $payload = [
                    'transaction_details' => [
                        'order_id'     => $orderId,
                        'gross_amount' => $amount,
                    ],
                    'credit_card' => [
                        'secure' => true,
                    ],
                    'customer_details' => [
                        'first_name' => $user ? ($user->nama_user ?? 'Penghuni') : 'Penghuni',
                        'email'      => $user ? ($user->email_user ?? 'user@example.com') : 'user@example.com',
                        'phone'      => $user ? ($user->nohp_user ?? '') : '',
                    ],
                    'item_details' => [
                        [
                            'id'       => 'BILL-' . $tagihan->id,
                            'price'    => $amount,
                            'quantity' => 1,
                            'name'     => $tagihan->nama_tagihan ?? ('Tagihan Kos - Kamar ' . ($kamar->nomor_kamar ?? '')),
                        ]
                    ]
                ];

                $response = Http::withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->withBasicAuth($serverKey, '')
                ->post($endpoint, $payload);

                if ($response->successful()) {
                    $snapToken = $response->json()['token'] ?? null;
                } else {
                    \Illuminate\Support\Facades\Log::error('Midtrans API error: ' . $response->body() . ' Status: ' . $response->status());
                    $snapToken = 'dummy-snap-token-fallback-' . uniqid();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Midtrans API exception: ' . $e->getMessage());
                $snapToken = 'dummy-snap-token-error-' . uniqid();
            }
        }

        $pembayaran = Pembayaran::create([
            'tanggal_bayar'     => null,
            'metode_pembayaran' => 'transfer_bank',
            'jumlah_bayar'      => $amount,
            'status_pembayaran' => 'pending',
            'payment_gateway'   => 'midtrans',
            'external_id'       => $orderId,
            'snap_token'        => $snapToken,
            'status_webhook'    => 'waiting',
            'id_tagihan'        => $tagihan->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token pembayaran berhasil dibuat.',
            'data'    => [
                'token'      => $snapToken,
                'order_id'   => $orderId,
                'amount'     => $amount,
            ],
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
        $orderId = $request->input('order_id') ?? $request->input('external_id');
        $statusStr = $request->input('transaction_status') ?? $request->input('status');

        if (empty($orderId) || empty($statusStr)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data'    => null,
                'errors'  => null,
            ], 422);
        }

        $pembayaran = Pembayaran::where('external_id', $orderId)->first();

        if (!$pembayaran) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran not found for this external_id',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        $statusMap = [
            'SETTLEMENT' => 'berhasil',
            'CAPTURE'    => 'berhasil',
            'PENDING'    => 'pending',
            'DENY'       => 'gagal',
            'EXPIRE'     => 'gagal',
            'CANCEL'     => 'gagal',
            'PAID'       => 'berhasil',
            'FAILED'     => 'gagal',
        ];

        $newStatus = $statusMap[strtoupper($statusStr)] ?? 'pending';

        $pembayaran->update([
            'status_pembayaran' => $newStatus,
            'status_webhook'    => 'received',
            'tanggal_bayar'     => $newStatus === 'berhasil' ? now() : $pembayaran->tanggal_bayar,
        ]);

        if ($newStatus === 'berhasil') {
            $pembayaran->tagihan->update(['status_tagihan' => 'lunas']);

            // Kirim notifikasi otomatis ke Penghuni, Pengelola, dan Owner (Prosedur 7c)
            try {
                $notifClient = new \Shared\MicroserviceClient(
                    env('NOTIFICATION_SERVICE_URL', 'http://localhost:8007')
                );

                $tagihan = $pembayaran->tagihan;
                if ($tagihan) {
                    $sewa = DB::table('sewa')->where('id', $tagihan->id_sewa)->first();
                    if ($sewa) {
                        $kamar = DB::table('kamar')->where('id', $sewa->id_kamar)->first();
                        if ($kamar) {
                            $kos = DB::table('kos')->where('id', $kamar->id_kos)->first();
                            if ($kos) {
                                // 1. Kirim ke Penghuni (tenant)
                                $notifClient->post('/api/notifikasi', [
                                    'id_user'      => $sewa->id_user,
                                    'judul'        => 'Pembayaran Berhasil',
                                    'pesan'        => "Tagihan sewa Anda untuk periode {$tagihan->bulan_tagihan} sebesar Rp " . number_format($pembayaran->jumlah_bayar, 0, ',', '.') . " telah sukses dibayar.",
                                    'tipe'         => 'pembayaran',
                                    'id_terkait'   => $pembayaran->id,
                                    'tipe_terkait' => 'pembayaran',
                                ]);

                                // 2. Kirim ke Pengelola Kos (jika ada)
                                if (!empty($kos->id_pengelola)) {
                                    $notifClient->post('/api/notifikasi', [
                                        'id_user'      => $kos->id_pengelola,
                                        'judul'        => 'Pemasukan Uang Sewa',
                                        'pesan'        => "Penghuni Kamar {$kamar->nomor_kamar} di Kos '{$kos->nama_kos}' telah membayar tagihan Rp " . number_format($pembayaran->jumlah_bayar, 0, ',', '.') . " secara lunas.",
                                        'tipe'         => 'pembayaran',
                                        'id_terkait'   => $pembayaran->id,
                                        'tipe_terkait' => 'pembayaran',
                                    ]);
                                }

                                // 3. Kirim ke Owner (Pemilik Kos)
                                if (!empty($kos->id_user)) {
                                    $notifClient->post('/api/notifikasi', [
                                        'id_user'      => $kos->id_user,
                                        'judul'        => 'Pemasukan Uang Sewa',
                                        'pesan'        => "Pembayaran sewa Kamar {$kamar->nomor_kamar} di Kos '{$kos->nama_kos}' sebesar Rp " . number_format($pembayaran->jumlah_bayar, 0, ',', '.') . " telah sukses diterima.",
                                        'tipe'         => 'pembayaran',
                                        'id_terkait'   => $pembayaran->id,
                                        'tipe_terkait' => 'pembayaran',
                                    ]);
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                //
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed successfully',
            'data'    => $pembayaran->fresh(),
            'errors'  => null,
        ]);
    }
}
