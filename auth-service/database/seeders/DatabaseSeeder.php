<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * This seeder runs all seeds for the microservices shared database.
     */
    public function run(): void
    {
        $this->seedUsers();
        $this->seedKos();
        $this->seedKamar();
        $this->seedAset();
        $this->seedMaintenance();
        $this->seedSewa();
        $this->seedTagihan();
        $this->seedPembayaran();
        $this->seedLaporanKerusakan();
        $this->seedNotifikasi();

        echo "✅ All seeders completed successfully!\n";
    }

    /**
     * Seed users table.
     */
    private function seedUsers(): void
    {
        DB::table('users')->insert([
            [
                'id'                => 1,
                'nama_user'         => 'Owner Kos Pak Budi',
                'email_user'        => 'owner@microservices.test',
                'password_user'     => Hash::make('password123'),
                'role'              => 'owner',
                'nohp_user'         => '081234567890',
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'id'                => 2,
                'nama_user'         => 'Pengelola Kos Bu Ani',
                'email_user'        => 'pengelola@microservices.test',
                'password_user'     => Hash::make('password123'),
                'role'              => 'pengelola_kos',
                'nohp_user'         => '081234567891',
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'id'                => 3,
                'nama_user'         => 'Ahmad Fauzi',
                'email_user'        => 'ahmad@microservices.test',
                'password_user'     => Hash::make('password123'),
                'role'              => 'user',
                'nohp_user'         => '081234567892',
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'id'                => 4,
                'nama_user'         => 'Siti Nurhaliza',
                'email_user'        => 'siti@microservices.test',
                'password_user'     => Hash::make('password123'),
                'role'              => 'user',
                'nohp_user'         => '081234567893',
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);

        echo "  → Users seeded (4 records)\n";
    }

    /**
     * Seed kos table.
     */
    private function seedKos(): void
    {
        DB::table('kos')->insert([
            [
                'id'         => 1,
                'nama_kos'   => 'Kos Mawar Putih Cabang Bandung',
                'alamat_kos' => 'Jl. Cihampelas No. 123, Bandung, Jawa Barat 40131',
                'id_user'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => 2,
                'nama_kos'   => 'Kos Mawar Putih Cabang Jakarta',
                'alamat_kos' => 'Jl. Sudirman No. 456, Jakarta Selatan, DKI Jakarta 12190',
                'id_user'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        echo "  → Kos seeded (2 records)\n";
    }

    /**
     * Seed kamar table.
     */
    private function seedKamar(): void
    {
        DB::table('kamar')->insert([
            [
                'id'              => 1,
                'nomor_kamar'     => 'A101',
                'kapasitas_kamar' => 1,
                'harga_kamar'     => 1500000.00,
                'status_kamar'    => 'terisi',
                'deskripsi_kamar' => 'Kamar standar dengan AC dan kamar mandi dalam',
                'id_kos'          => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => 2,
                'nomor_kamar'     => 'A102',
                'kapasitas_kamar' => 1,
                'harga_kamar'     => 1500000.00,
                'status_kamar'    => 'terisi',
                'deskripsi_kamar' => 'Kamar standar dengan AC dan kamar mandi dalam',
                'id_kos'          => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => 3,
                'nomor_kamar'     => 'B201',
                'kapasitas_kamar' => 2,
                'harga_kamar'     => 2500000.00,
                'status_kamar'    => 'tersedia',
                'deskripsi_kamar' => 'Kamar premium dengan AC, kamar mandi dalam, dan balkon',
                'id_kos'          => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => 4,
                'nomor_kamar'     => 'C101',
                'kapasitas_kamar' => 1,
                'harga_kamar'     => 1800000.00,
                'status_kamar'    => 'tersedia',
                'deskripsi_kamar' => 'Kamar standar plus dengan AC dan WiFi',
                'id_kos'          => 2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        echo "  → Kamar seeded (4 records)\n";
    }

    /**
     * Seed aset table.
     */
    private function seedAset(): void
    {
        DB::table('aset')->insert([
            [
                'id'                  => 1,
                'nama_aset'           => 'AC Daikin 1PK',
                'tanggal_pembelian'   => '2024-01-15',
                'harga'               => 4500000.00,
                'kondisi'             => 'baik',
                'id_kos'              => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'id'                  => 2,
                'nama_aset'           => 'Kasur Spring Bed 120x200',
                'tanggal_pembelian'   => '2024-01-15',
                'harga'               => 2500000.00,
                'kondisi'             => 'baik',
                'id_kos'              => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'id'                  => 3,
                'nama_aset'           => 'Water Heater Ariston',
                'tanggal_pembelian'   => '2024-02-01',
                'harga'               => 1800000.00,
                'kondisi'             => 'rusak_ringan',
                'id_kos'              => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'id'                  => 4,
                'nama_aset'           => 'AC Samsung 1.5PK',
                'tanggal_pembelian'   => '2024-03-10',
                'harga'               => 5500000.00,
                'kondisi'             => 'baik',
                'id_kos'              => 2,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        ]);

        echo "  → Aset seeded (4 records)\n";
    }

    /**
     * Seed maintenance table.
     */
    private function seedMaintenance(): void
    {
        DB::table('maintenance')->insert([
            [
                'id'                 => 1,
                'deskripsi'          => 'Perbaikan Water Heater - penggantian elemen pemanas',
                'biaya'              => 350000.00,
                'tanggal_perbaikan'  => '2024-06-15',
                'status'             => 'selesai',
                'id_aset'            => 3,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'id'                 => 2,
                'deskripsi'          => 'Service AC rutin - cuci dan tambah freon',
                'biaya'              => 150000.00,
                'tanggal_perbaikan'  => now()->addDays(7)->toDateString(),
                'status'             => 'dijadwalkan',
                'id_aset'            => 1,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ]);

        echo "  → Maintenance seeded (2 records)\n";
    }

    /**
     * Seed sewa table.
     */
    private function seedSewa(): void
    {
        DB::table('sewa')->insert([
            [
                'id'              => 1,
                'tanggal_masuk'   => '2024-06-01',
                'tanggal_keluar'  => '2025-06-01',
                'status_sewa'     => 'aktif',
                'id_user'         => 3,
                'id_kamar'        => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => 2,
                'tanggal_masuk'   => '2024-07-01',
                'tanggal_keluar'  => '2025-07-01',
                'status_sewa'     => 'aktif',
                'id_user'         => 4,
                'id_kamar'        => 2,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        echo "  → Sewa seeded (2 records)\n";
    }

    /**
     * Seed tagihan table.
     */
    private function seedTagihan(): void
    {
        DB::table('tagihan')->insert([
            [
                'id'                   => 1,
                'bulan_tagihan'        => now()->format('Y-m'),
                'tanggal_jatuhtempo'   => now()->addDays(5)->toDateString(),
                'jumlah_tagihan'       => 1500000.00,
                'status_tagihan'       => 'belum_bayar',
                'id_sewa'              => 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'id'                   => 2,
                'bulan_tagihan'        => now()->format('Y-m'),
                'tanggal_jatuhtempo'   => now()->addDays(10)->toDateString(),
                'jumlah_tagihan'       => 1500000.00,
                'status_tagihan'       => 'belum_bayar',
                'id_sewa'              => 2,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'id'                   => 3,
                'bulan_tagihan'        => now()->subMonth()->format('Y-m'),
                'tanggal_jatuhtempo'   => now()->subDays(5)->toDateString(),
                'jumlah_tagihan'       => 1500000.00,
                'status_tagihan'       => 'lunas',
                'id_sewa'              => 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
        ]);

        echo "  → Tagihan seeded (3 records)\n";
    }

    /**
     * Seed pembayaran table.
     */
    private function seedPembayaran(): void
    {
        DB::table('pembayaran')->insert([
            [
                'id'                  => 1,
                'tanggal_bayar'       => now()->subDays(3),
                'metode_pembayaran'   => 'transfer_bank',
                'jumlah_bayar'        => 1500000.00,
                'status_pembayaran'   => 'berhasil',
                'payment_gateway'     => 'manual',
                'external_id'         => null,
                'status_webhook'      => 'waiting',
                'id_tagihan'          => 3,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        ]);

        echo "  → Pembayaran seeded (1 record)\n";
    }

    /**
     * Seed laporankerusakan table.
     */
    private function seedLaporanKerusakan(): void
    {
        DB::table('laporankerusakan')->insert([
            [
                'id'              => 1,
                'tanggal_lapor'   => now()->subDays(2),
                'status_laporan'  => 'diproses',
                'deskripsi'       => 'Water heater di kamar mandi tidak mengeluarkan air panas, sudah dicoba restart tapi tetap dingin.',
                'id_user'         => 3,
                'id_aset'         => 3,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        echo "  → Laporan Kerusakan seeded (1 record)\n";
    }

    /**
     * Seed notifikasi table.
     */
    private function seedNotifikasi(): void
    {
        DB::table('notifikasi')->insert([
            [
                'id'            => 1,
                'id_user'       => 3,
                'judul'         => 'Pengingat Pembayaran',
                'pesan'         => 'Tagihan bulan ' . now()->format('Y-m') . ' akan jatuh tempo 5 hari lagi. Jumlah: Rp 1.500.000',
                'tipe'          => 'pembayaran',
                'dibaca'        => false,
                'id_terkait'    => 1,
                'tipe_terkait'  => 'tagihan',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => 2,
                'id_user'       => 3,
                'judul'         => 'Laporan Diproses',
                'pesan'         => 'Laporan kerusakan Water Heater sedang diproses oleh pengelola kos.',
                'tipe'          => 'laporan',
                'dibaca'        => true,
                'id_terkait'    => 1,
                'tipe_terkait'  => 'laporankerusakan',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);

        echo "  → Notifikasi seeded (2 records)\n";
    }
}
