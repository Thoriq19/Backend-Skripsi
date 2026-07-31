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
        Schema::table('aset', function (Blueprint $table) {
            // Modify conditions enum in MySQL/SQLite
            $table->enum('kondisi', ['baik', 'rusak_ringan', 'rusak_berat', 'perlu_di_ganti'])->default('baik')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->enum('kondisi', ['baik', 'rusak_ringan', 'rusak_berat'])->default('baik')->change();
        });
    }
};
