<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PembayaranWebhookTest extends TestCase
{
    use RefreshDatabase;

    private int $tagihanId;
    private string $externalId = 'invoice-123456';

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Insert User
        $userId = DB::table('users')->insertGetId([
            'nama_user' => 'Penghuni Test',
            'email_user' => 'penghuni@test.com',
            'password_user' => bcrypt('password123'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Insert Kos
        $kosId = DB::table('kos')->insertGetId([
            'nama_kos' => 'Kos Mawar',
            'alamat_kos' => 'Jl. Mawar No. 12',
            'id_user' => $userId, // owner
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Insert Kamar
        $kamarId = DB::table('kamar')->insertGetId([
            'nomor_kamar' => '101',
            'tipe_kamar' => 'reguler',
            'harga_kamar' => 800000,
            'kapasitas_kamar' => 1,
            'status_kamar' => 'tersedia',
            'id_kos' => $kosId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Insert Sewa
        $sewaId = DB::table('sewa')->insertGetId([
            'tanggal_masuk' => now()->toDateString(),
            'tanggal_keluar' => now()->addMonths(1)->toDateString(),
            'status_sewa' => 'aktif',
            'id_user' => $userId,
            'id_kamar' => $kamarId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Insert Tagihan
        $this->tagihanId = DB::table('tagihan')->insertGetId([
            'bulan_tagihan' => 'Juli 2026',
            'tanggal_jatuhtempo' => now()->addDays(5)->toDateString(),
            'jumlah_tagihan' => 800000,
            'status_tagihan' => 'belum_bayar',
            'id_sewa' => $sewaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. Insert Pembayaran
        DB::table('pembayaran')->insertGetId([
            'metode_pembayaran' => 'e_wallet',
            'jumlah_bayar' => 800000,
            'status_pembayaran' => 'pending',
            'external_id' => $this->externalId,
            'id_tagihan' => $this->tagihanId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Menguji callback webhook memperbarui status pembayaran dan tagihan menjadi lunas/berhasil.
     */
    public function test_webhook_updates_payment_status_to_success()
    {
        $response = $this->postJson('/api/pembayaran/webhook', [
            'external_id' => $this->externalId,
            'status' => 'PAID',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Webhook processed successfully',
                 ]);

        // Assert payment status updated
        $this->assertDatabaseHas('pembayaran', [
            'external_id' => $this->externalId,
            'status_pembayaran' => 'berhasil',
            'status_webhook' => 'received',
        ]);

        // Assert tagihan status updated
        $this->assertDatabaseHas('tagihan', [
            'id' => $this->tagihanId,
            'status_tagihan' => 'lunas',
        ]);
    }

    /**
     * Menguji kegagalan webhook jika parameter wajib tidak dikirim.
     */
    public function test_webhook_fails_on_validation_failure()
    {
        $response = $this->postJson('/api/pembayaran/webhook', [
            'external_id' => $this->externalId,
            // missing status
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Validation failed',
                 ]);
    }

    /**
     * Menguji penolakan webhook jika external_id tidak ditemukan.
     */
    public function test_webhook_returns_404_for_unknown_external_id()
    {
        $response = $this->postJson('/api/pembayaran/webhook', [
            'external_id' => 'unknown-id-123',
            'status' => 'PAID',
        ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Pembayaran not found for this external_id',
                 ]);
    }
}
