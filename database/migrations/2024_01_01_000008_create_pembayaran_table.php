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
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->timestamp('tanggal_bayar')->nullable();
            $table->enum('metode_pembayaran', ['transfer_bank', 'e_wallet', 'tunai']);
            $table->decimal('jumlah_bayar', 14, 2);
            $table->enum('status_pembayaran', ['pending', 'berhasil', 'gagal'])->default('pending');
            $table->enum('payment_gateway', ['manual', 'xendit', 'midtrans'])->default('manual');
            $table->string('external_id')->nullable()->unique();
            $table->enum('status_webhook', ['waiting', 'received', 'verified'])->default('waiting');
            $table->unsignedBigInteger('id_tagihan');
            $table->timestamps();

            $table->foreign('id_tagihan')->references('id')->on('tagihan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
