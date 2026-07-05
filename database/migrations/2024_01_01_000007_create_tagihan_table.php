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
        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->string('bulan_tagihan');
            $table->date('tanggal_jatuhtempo');
            $table->decimal('jumlah_tagihan', 14, 2);
            $table->enum('status_tagihan', ['belum_bayar', 'lunas', 'terlambat'])->default('belum_bayar');
            $table->unsignedBigInteger('id_sewa');
            $table->timestamps();

            $table->foreign('id_sewa')->references('id')->on('sewa')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan');
    }
};
