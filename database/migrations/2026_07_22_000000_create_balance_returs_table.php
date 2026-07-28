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
        Schema::create('balance_returs', function (Blueprint $table) {
            $table->id();

            // Kolom "No" asli dari file export (bukan primary key, hanya nomor urut sumber)
            $table->unsignedInteger('source_no')->nullable();

            // Tanggal retur dibuat (dipakai untuk filter rentang tanggal)
            $table->date('date_retur')->nullable();

            // Informasi retur
            $table->string('no_retur')->nullable();
            $table->string('rev_no')->nullable();
            $table->string('no_from_customer')->nullable();
            $table->string('customer_name')->nullable();

            // Informasi barang
            $table->string('code_item')->nullable();
            $table->string('part_no')->nullable();
            $table->string('part_name')->nullable();
            $table->string('model')->nullable();
            $table->string('product_status')->nullable(); // mis. Job / Continue

            // Qty retur
            $table->decimal('qty_retur', 15, 2)->nullable();
            $table->string('unit')->nullable();

            // Receiving part
            $table->decimal('qty_receiving_part', 15, 2)->nullable();
            $table->decimal('qty_pending_receiving_part', 15, 2)->nullable();
            $table->string('status_receiving')->nullable(); // CLOSE / OPEN

            // Delivery part
            $table->decimal('qty_delivery_part', 15, 2)->nullable();
            $table->decimal('qty_pending_delivery_part', 15, 2)->nullable();
            $table->string('status_delivery')->nullable(); // CLOSE / OPEN

            $table->decimal('stock_realtime', 15, 2)->nullable();
            $table->string('final_status')->nullable(); // CLOSE / OPEN

            $table->text('note')->nullable();
            $table->string('pic_ppic_delivery')->nullable();

            // Menyimpan asal file import, untuk audit / penelusuran data
            $table->string('import_batch')->nullable();

            $table->timestamps();

            $table->index('date_retur');
            $table->index('no_retur');
            $table->index('code_item');
            $table->index('customer_name');
            $table->index('status_receiving');
            $table->index('status_delivery');
            $table->index('final_status');
            $table->unique(['no_retur', 'code_item'], 'balance_returs_no_retur_code_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_returs');
    }
};
