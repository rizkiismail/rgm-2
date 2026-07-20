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
        Schema::create('receiving_goods', function (Blueprint $table) {
            $table->id();

            // Kolom "No" asli dari file export (bukan primary key, hanya nomor urut sumber)
            $table->unsignedInteger('source_no')->nullable();

            // Tanggal barang diterima (dipakai untuk filter rentang tanggal)
            $table->dateTime('date_income')->nullable();

            // Informasi BSTHP
            $table->dateTime('bsthp_date')->nullable();
            $table->string('bsthp_no')->nullable();
            $table->string('verify_no')->nullable();
            $table->string('verify_by')->nullable(); // Nama PIC yang memverifikasi

            // Informasi barang
            $table->string('code_item')->nullable();
            $table->string('part_name')->nullable();
            $table->string('part_no')->nullable();
            $table->string('model')->nullable();
            $table->decimal('qty', 15, 2)->nullable();
            $table->string('unit')->nullable();

            // Barcode
            $table->string('bsthp_barcode_no')->nullable();
            $table->string('label_barcode_no')->nullable();

            // Customer
            $table->string('customer')->nullable();

            // Menyimpan asal file import, untuk audit / penelusuran data
            $table->string('import_batch')->nullable();

            $table->timestamps();

            $table->index('date_income');
            $table->index('bsthp_no');
            $table->index('code_item');
            $table->index('verify_by');
            $table->index('customer');
            $table->index('label_barcode_no');
            $table->unique(['bsthp_no', 'label_barcode_no'], 'receiving_goods_bsthp_label_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiving_goods');
    }
};
