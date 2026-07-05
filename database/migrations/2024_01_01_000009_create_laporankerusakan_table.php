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
        Schema::create('laporankerusakan', function (Blueprint $table) {
            $table->id();
            $table->timestamp('tanggal_lapor')->useCurrent();
            $table->enum('status_laporan', ['dilaporkan', 'diproses', 'selesai'])->default('dilaporkan');
            $table->text('deskripsi');
            $table->string('foto')->nullable();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_aset');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_aset')->references('id')->on('aset')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporankerusakan');
    }
};
