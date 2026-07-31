<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Update data lama agar tidak menyebabkan warning 1265
            DB::statement("UPDATE pembayaran SET payment_gateway = 'midtrans' WHERE payment_gateway = 'manual'");
            DB::statement("UPDATE pembayaran SET metode_pembayaran = 'transfer_bank' WHERE metode_pembayaran = 'tunai'");

            // Ubah enum kolom pembayaran tanpa menghapus data lain
            DB::statement("ALTER TABLE pembayaran MODIFY COLUMN metode_pembayaran ENUM('transfer_bank', 'e_wallet') NOT NULL");
            DB::statement("ALTER TABLE pembayaran MODIFY COLUMN payment_gateway ENUM('xendit', 'midtrans') NOT NULL DEFAULT 'midtrans'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE pembayaran MODIFY COLUMN metode_pembayaran ENUM('transfer_bank', 'e_wallet', 'tunai') NOT NULL");
            DB::statement("ALTER TABLE pembayaran MODIFY COLUMN payment_gateway ENUM('manual', 'xendit', 'midtrans') NOT NULL DEFAULT 'manual'");
        }
    }
};
