<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan kolom 'harga_sewa' pada tabel sewa
     * untuk menyimpan harga sewa yang terkunci saat deal,
     * sehingga perubahan harga kamar tidak mempengaruhi penghuni lama.
     * Digunakan untuk auto-generate tagihan bulanan (Prosedur 4d & 6b).
     */
    public function up(): void
    {
        Schema::table('sewa', function (Blueprint $table) {
            $table->decimal('harga_sewa', 12, 2)->nullable()->after('status_sewa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sewa', function (Blueprint $table) {
            $table->dropColumn('harga_sewa');
        });
    }
};
