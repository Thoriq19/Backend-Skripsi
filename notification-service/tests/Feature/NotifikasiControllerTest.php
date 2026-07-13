<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotifikasiControllerTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;
    private int $unreadNotifId;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Insert User
        $this->userId = DB::table('users')->insertGetId([
            'nama_user' => 'User Test',
            'email_user' => 'user@test.com',
            'password_user' => bcrypt('password123'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Insert notifications
        $this->unreadNotifId = DB::table('notifikasi')->insertGetId([
            'id_user' => $this->userId,
            'judul' => 'Tagihan Baru',
            'pesan' => 'Anda memiliki tagihan sewa baru.',
            'tipe' => 'pembayaran',
            'dibaca' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notifikasi')->insert([
            [
                'id_user' => $this->userId,
                'judul' => 'Laporan Selesai',
                'pesan' => 'Laporan kerusakan AC telah diselesaikan.',
                'tipe' => 'laporan',
                'dibaca' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_user' => $this->userId,
                'judul' => 'Informasi Kos',
                'pesan' => 'Selamat datang di E-Boarding House.',
                'tipe' => 'info',
                'dibaca' => true, // Already read
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Test fetching unread notification count.
     */
    public function test_fetch_unread_notifications_count()
    {
        $response = $this->getJson("/api/notifikasi/belum-dibaca?id_user={$this->userId}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Unread count retrieved',
                     'data' => [
                         'belum_dibaca' => 2,
                     ]
                 ]);
    }

    /**
     * Test marking a specific notification as read.
     */
    public function test_user_can_mark_notification_as_read()
    {
        $response = $this->putJson("/api/notifikasi/{$this->unreadNotifId}/baca");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Notifikasi marked as read',
                 ]);

        $this->assertDatabaseHas('notifikasi', [
            'id' => $this->unreadNotifId,
            'dibaca' => true,
        ]);
    }

    /**
     * Test unread count endpoint validation fails without id_user.
     */
    public function test_unread_count_fails_without_user_id()
    {
        $response = $this->getJson("/api/notifikasi/belum-dibaca");

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'id_user is required',
                 ]);
    }
}
