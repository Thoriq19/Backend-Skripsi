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
        Schema::create('maintenance', function (Blueprint $table) {
            $table->id();
            $table->text('deskripsi');
            $table->decimal('biaya', 12, 2);
            $table->date('tanggal_perbaikan');
            $table->enum('status', ['dijadwalkan', 'sedang_dikerjakan', 'selesai'])->default('dijadwalkan');
            $table->unsignedBigInteger('id_aset');
            $table->timestamps();

            $table->foreign('id_aset')->references('id')->on('aset')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance');
    }
};
