<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KosControllerTest extends TestCase
{
    use RefreshDatabase;

    private int $ownerId;
    private int $pengelolaId;

    protected function setUp(): void
    {
        parent::setUp();

        // Insert mock users directly into database to bypass model mismatches
        $this->ownerId = DB::table('users')->insertGetId([
            'nama_user' => 'Owner Test',
            'email_user' => 'owner@test.com',
            'password_user' => bcrypt('password123'),
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->pengelolaId = DB::table('users')->insertGetId([
            'nama_user' => 'Pengelola Test',
            'email_user' => 'pengelola@test.com',
            'password_user' => bcrypt('password123'),
            'role' => 'pengelola_kos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Menguji pemilik kos dapat membuat cabang kos baru.
     */
    public function test_owner_can_create_kos_branch()
    {
        $response = $this->postJson('/api/kos', [
            'nama_kos' => 'Kos Baru Bandung',
            'alamat_kos' => 'Jl. Baru No. 10',
            'jumlah_kamar' => 10,
            'id_user' => $this->ownerId,
            'auth_user_role' => 'owner', // Simulated role from gateway
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Kos created successfully',
                 ]);

        $this->assertDatabaseHas('kos', [
            'nama_kos' => 'Kos Baru Bandung',
            'id_user' => $this->ownerId,
        ]);
    }

    /**
      * Menguji selain pemilik kos tidak dapat membuat cabang kos baru.
      */
    public function test_non_owner_cannot_create_kos_branch()
    {
        $response = $this->postJson('/api/kos', [
            'nama_kos' => 'Kos Baru Bandung',
            'alamat_kos' => 'Jl. Baru No. 10',
            'jumlah_kamar' => 10,
            'id_user' => $this->ownerId,
            'auth_user_role' => 'pengelola_kos', // Non-owner role
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Forbidden. Only owners can create kos data.',
                 ]);
    }

    /**
     * Menguji pemilik kos dapat menugaskan pengelola ke cabang kos.
     */
    public function test_owner_can_assign_pengelola_to_kos()
    {
        // First create the kos
        $kosId = DB::table('kos')->insertGetId([
            'nama_kos' => 'Kos Melati',
            'alamat_kos' => 'Jl. Melati No. 5',
            'jumlah_kamar' => 5,
            'id_user' => $this->ownerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign pengelola to it
        $response = $this->putJson("/api/kos/{$kosId}", [
            'id_pengelola' => $this->pengelolaId,
            'auth_user_role' => 'owner',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Kos updated successfully',
                 ]);

        $this->assertDatabaseHas('kos', [
            'id' => $kosId,
            'id_pengelola' => $this->pengelolaId,
        ]);
    }
}
