<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan kolom 'jumlah_kamar' pada tabel kos
     * sesuai Prosedur Pengaturan data master kos oleh Pemilik Kos.
     */
    public function up(): void
    {
        Schema::table('kos', function (Blueprint $table) {
            $table->integer('jumlah_kamar')->default(0)->after('alamat_kos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kos', function (Blueprint $table) {
            $table->dropColumn('jumlah_kamar');
        });
    }
};
