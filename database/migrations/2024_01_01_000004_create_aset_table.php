<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aset', function (Blueprint $table) {
            $table->id();
            $table->string('nama_aset');
            $table->date('tanggal_pembelian');
            $table->decimal('harga', 12, 2);
            $table->enum('kondisi', ['baik', 'rusak_ringan', 'rusak_berat'])->default('baik');
            $table->unsignedBigInteger('id_kos');
            $table->unsignedBigInteger('id_kamar')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_kos')->references('id')->on('kos')->onDelete('cascade');
            $table->foreign('id_kamar')->references('id')->on('kamar')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aset');
    }
};
