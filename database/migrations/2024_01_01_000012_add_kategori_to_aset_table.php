<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan kolom 'kategori' pada tabel aset
     * sesuai Prosedur 5a (Pengelolaan Fasilitas dan Aset).
     */
    public function up(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->string('kategori')->nullable()->after('nama_aset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }
};
