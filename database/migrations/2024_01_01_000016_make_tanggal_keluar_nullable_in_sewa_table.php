<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mengubah kolom 'tanggal_keluar' pada tabel 'sewa' menjadi nullable.
     * Hal ini karena sistem sewa bersifat bergulir (recurring)
     * di mana tanggal keluar tidak diketahui di awal dan baru diisi saat checkout.
     */
    public function up(): void
    {
        Schema::table('sewa', function (Blueprint $table) {
            $table->date('tanggal_keluar')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sewa', function (Blueprint $table) {
            $table->date('tanggal_keluar')->nullable(false)->change();
        });
    }
};
