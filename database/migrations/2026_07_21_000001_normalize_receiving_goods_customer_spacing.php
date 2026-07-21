<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Beberapa data lama tersimpan dengan spasi ganda pada nama customer
     * (mis. "ROKI  INDONESIA, PT."), sehingga tidak cocok dengan nama pada
     * tabel master `customers` (mis. "ROKI INDONESIA, PT."). Migration ini
     * merapikan spasi tersebut supaya join/pencocokan Line berjalan benar.
     */
    public function up(): void
    {
        DB::table('receiving_goods')
            ->whereNotNull('customer')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) {
                foreach ($rows as $row) {
                    $clean = trim(preg_replace('/\s+/', ' ', (string) $row->customer));
                    if ($clean !== $row->customer && $clean !== '') {
                        DB::table('receiving_goods')->where('id', $row->id)->update(['customer' => $clean]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak ada rollback untuk normalisasi data (tidak merusak data, hanya merapikan spasi).
    }
};
