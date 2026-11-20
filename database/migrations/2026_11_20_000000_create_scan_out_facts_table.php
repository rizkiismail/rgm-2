<?php

use App\Services\ScanOutFacts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel "fakta" sempit untuk dashboard Scan Out.
     *
     * Dashboard Scan Out menjalankan belasan query COUNT(DISTINCT barcode / outgoing_no /
     * code_item / ...) pada kolom teks panjang di tabel scan_outs (600rb+ baris). Itu sangat
     * lambat (COUNT DISTINCT teks dengan collation unicode_ci), sehingga melewati
     * max_execution_time.
     *
     * scan_out_facts menyimpan satu baris per baris scan_outs, tetapi kolom teks diganti
     * angka (ID kamus / hash), jadi perhitungan DISTINCT tetap EKSAK namun jauh lebih cepat.
     * scan_out_terms adalah kamus nilai teks (customer, code item, PIC, outgoing type, line).
     */
    public function up(): void
    {
        Schema::create('scan_out_terms', function (Blueprint $table) {
            $table->increments('id');
            // 1 = customer, 2 = code item, 3 = PIC (scan by), 4 = outgoing type, 5 = line label
            $table->unsignedTinyInteger('kind');
            $table->string('value');
            // code item -> part name; PIC -> NIK (nilai MIN, sama seperti query lama)
            $table->string('extra')->nullable();

            $table->unique(['kind', 'value'], 'scan_out_terms_kind_value_unique');
        });

        Schema::create('scan_out_facts', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary(); // = scan_outs.id
            $table->date('d');
            $table->unsignedTinyInteger('h');
            $table->unsignedSmallInteger('cust')->nullable();
            $table->unsignedMediumInteger('item')->nullable();
            $table->unsignedSmallInteger('pic')->nullable();
            $table->unsignedSmallInteger('typ')->nullable();
            $table->unsignedSmallInteger('line_term')->nullable();
            $table->unsignedTinyInteger('line_sort')->default(99);
            $table->unsignedBigInteger('outg')->nullable(); // hash No. Outgoing
            $table->unsignedBigInteger('bc')->nullable();   // hash Barcode
            $table->decimal('qty', 15, 2)->nullable();

            $table->index('d');
        });

        // Isi awal dari data yang sudah ada (via CLI tidak ada batas waktu).
        app(ScanOutFacts::class)->rebuild();
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_out_facts');
        Schema::dropIfExists('scan_out_terms');
    }
};
