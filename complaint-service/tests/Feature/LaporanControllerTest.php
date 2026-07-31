<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LaporanControllerTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;
    private int $asetId;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Insert User
        $this->userId = DB::table('users')->insertGetId([
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
            'id_user' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Insert Aset
        $this->asetId = DB::table('aset')->insertGetId([
            'nama_aset' => 'AC Kamar 101',
            'kategori' => 'elektronik',
            'kondisi' => 'baik',
            'tanggal_pembelian' => now()->toDateString(),
            'harga' => 2000000,
            'id_kos' => $kosId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Menguji penghuni dapat mengirimkan laporan kerusakan fasilitas kos.
     */
    public function test_user_can_submit_damage_report()
    {
        $response = $this->postJson('/api/laporan', [
            'deskripsi' => 'AC kamar tidak dingin dan berbunyi bising.',
            'id_user' => $this->userId,
            'id_aset' => $this->asetId,
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Laporan created successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'deskripsi',
                         'id_user',
                         'id_aset',
                         'tanggal_lapor',
                     ]
                 ]);

        $this->assertDatabaseHas('laporankerusakan', [
            'deskripsi' => 'AC kamar tidak dingin dan berbunyi bising.',
            'id_user' => $this->userId,
            'id_aset' => $this->asetId,
            'status_laporan' => 'dilaporkan',
        ]);
    }

    /**
     * Menguji kegagalan kirim aduan jika kolom penting tidak diisi.
     */
    public function test_report_submission_fails_on_validation_failure()
    {
        $response = $this->postJson('/api/laporan', [
            'id_user' => $this->userId,
            // missing deskripsi and id_aset
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Validation failed',
                 ]);
    }
}
