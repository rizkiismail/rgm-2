<?php

use App\Services\ReceivingGoodsRollup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ringkasan (rollup) untuk dashboard Receiving Goods.
     *
     * Dashboard sebelumnya menjalankan belasan query COUNT(DISTINCT ...) yang
     * memindai seluruh tabel receiving_goods (600rb+ baris) setiap dibuka,
     * sehingga melewati max_execution_time. Tabel ini menyimpan data yang sudah
     * dikelompokkan per (tanggal, jam, BSTHP, customer, PIC, code item) -- sekitar
     * 7x lebih sedikit baris -- sehingga semua statistik dashboard cukup membaca
     * tabel kecil ini.
     */
    public function up(): void
    {
        Schema::create('receiving_goods_rollup', function (Blueprint $table) {
            $table->id();
            $table->date('work_date');
            $table->unsignedTinyInteger('work_hour');
            $table->string('bsthp_no')->nullable();
            $table->string('customer')->nullable();
            $table->string('verify_by')->nullable();
            $table->string('code_item')->nullable();
            $table->unsignedInteger('row_count');
            $table->unsignedInteger('label_count');
            $table->decimal('qty_sum', 18, 2)->default(0);

            $table->index('work_date');
            $table->index('customer');
            $table->index('verify_by');
            $table->index('code_item');
        });

        // Halaman Upload menjalankan SELECT DISTINCT import_batch; tanpa index
        // query itu memindai seluruh tabel setiap halaman dibuka.
        foreach (['receiving_goods', 'scan_outs', 'balance_returs'] as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'import_batch')) {
                Schema::table($name, function (Blueprint $table) use ($name) {
                    $table->index('import_batch', $name.'_import_batch_index');
                });
            }
        }

        // Isi awal dari data yang sudah ada (via CLI tidak ada batas waktu).
        app(ReceivingGoodsRollup::class)->rebuild();
    }

    public function down(): void
    {
        foreach (['receiving_goods', 'scan_outs', 'balance_returs'] as $name) {
            if (Schema::hasTable($name)) {
                Schema::table($name, function (Blueprint $table) use ($name) {
                    $table->dropIndex($name.'_import_batch_index');
                });
            }
        }

        Schema::dropIfExists('receiving_goods_rollup');
    }
};
