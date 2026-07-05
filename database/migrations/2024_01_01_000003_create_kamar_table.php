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
        Schema::create('kamar', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kamar');
            $table->integer('kapasitas_kamar');
            $table->decimal('harga_kamar', 12, 2);
            $table->enum('status_kamar', ['tersedia', 'terisi', 'maintenance'])->default('tersedia');
            $table->text('deskripsi_kamar')->nullable();
            $table->unsignedBigInteger('id_kos');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_kos')->references('id')->on('kos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kamar');
    }
};
