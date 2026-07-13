<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan kolom 'dokumen_pendukung' pada tabel users
     * untuk menyimpan path/URL dokumen identitas penghuni (KTP, dll)
     * sesuai Prosedur 4b (pendaftaran penghuni dengan dokumen pendukung).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dokumen_pendukung')->nullable()->after('nohp_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dokumen_pendukung');
        });
    }
};
