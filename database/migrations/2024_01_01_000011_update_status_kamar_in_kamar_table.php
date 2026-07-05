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
        Schema::table('kamar', function (Blueprint $table) {
            $table->enum('status_kamar', ['tersedia', 'terisi', 'maintenance', 'tidak_tersedia', 'segera'])
                  ->default('tersedia')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kamar', function (Blueprint $table) {
            $table->enum('status_kamar', ['tersedia', 'terisi', 'maintenance'])
                  ->default('tersedia')
                  ->change();
        });
    }
};
