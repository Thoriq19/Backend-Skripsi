<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan kolom 'tipe_kamar' pada tabel kamar
     * sesuai Prosedur 9b (klasifikasi tipe unit kamar).
     */
    public function up(): void
    {
        Schema::table('kamar', function (Blueprint $table) {
            $table->string('tipe_kamar')->nullable()->after('nomor_kamar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kamar', function (Blueprint $table) {
            $table->dropColumn('tipe_kamar');
        });
    }
};
