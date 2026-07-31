<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CleanDemoSeeder extends Seeder
{
    /**
     * Run the completely empty demo seeder.
     * Keeps ONLY essential login user accounts and wipes 100% of all content data.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // Truncate all content tables to 0 records
        DB::table('laporankerusakan')->truncate();
        DB::table('notifikasi')->truncate();
        DB::table('pembayaran')->truncate();
        DB::table('tagihan')->truncate();
        DB::table('sewa')->truncate();
        DB::table('maintenance')->truncate();
        DB::table('aset')->truncate();
        DB::table('kamar')->truncate();
        DB::table('kos')->truncate();
        DB::table('users')->truncate();

        Schema::enableForeignKeyConstraints();

        // Seed ONLY 3 Essential User Accounts for Demo Login
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
                'nama_user'         => 'Ahmad Fauzi (Penghuni)',
                'email_user'        => 'ahmad@microservices.test',
                'password_user'     => Hash::make('password123'),
                'role'              => 'user',
                'nohp_user'         => '081234567892',
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);

        echo "✨ 100% Empty Content Demo Database Seeded Successfully!\n";
        echo "   - Users Left: 3 Accounts (Owner, Pengelola, Penghuni)\n";
        echo "   - Content Wiped: 0 Kos, 0 Kamar, 0 Aset, 0 Maintenance, 0 Sewa, 0 Tagihan, 0 Pembayaran, 0 Laporan Kerusakan!\n";
    }
}
