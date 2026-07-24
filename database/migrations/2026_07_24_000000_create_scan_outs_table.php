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
        Schema::create('scan_outs', function (Blueprint $table) {
            $table->id();

            // Kolom "No" asli dari file export (bukan primary key, hanya nomor urut sumber)
            $table->unsignedInteger('source_no')->nullable();

            // Lokasi rak asal barang di-scan, mis. "Line 09|Aa|Aa5"
            $table->string('row_location')->nullable();

            // Informasi customer & barang
            $table->string('customer_name')->nullable();
            $table->string('code_item')->nullable();
            $table->string('part_name')->nullable();
            $table->string('part_no')->nullable();
            $table->string('model')->nullable();

            // Barcode yang di-scan out
            $table->string('barcode')->nullable();

            // PIC yang melakukan scan
            $table->string('scan_by_nik')->nullable();
            $table->string('scan_by_name')->nullable();

            // Informasi outgoing (pengiriman keluar)
            $table->string('outgoing_no')->nullable();
            $table->string('customer_to')->nullable();
            $table->string('outgoing_type')->nullable(); // mis. Regular / Non Category

            // Tanggal & waktu scan (dipakai untuk filter rentang tanggal)
            $table->dateTime('scan_date')->nullable();

            $table->decimal('qty', 15, 2)->nullable();
            $table->string('unit')->nullable();

            // Menyimpan asal file import, untuk audit / penelusuran data
            $table->string('import_batch')->nullable();

            $table->timestamps();

            $table->index('scan_date');
            $table->index('customer_name');
            $table->index('code_item');
            $table->index('barcode');
            $table->index('scan_by_name');
            $table->index('outgoing_type');
            $table->index('outgoing_no');
            $table->unique(['barcode', 'scan_date', 'outgoing_no'], 'scan_outs_barcode_date_outgoing_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_outs');
    }
};
