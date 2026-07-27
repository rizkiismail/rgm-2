<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel cache standar Laravel. Dibutuhkan supaya CACHE_STORE=database bisa
     * dipakai -- ini penting karena aplikasi berjalan di Vercel (serverless):
     * filesystem lokal bersifat sementara/read-only, sehingga cache driver
     * "file" (default Laravel) tidak bisa diandalkan untuk menyimpan hasil
     * cache antar request (Cache::remember() pada dropdown/opsi filter).
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
