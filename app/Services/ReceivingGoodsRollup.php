<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Membangun tabel ringkasan `receiving_goods_rollup` dari `receiving_goods`.
 *
 * Satu baris ringkasan = satu kombinasi (tanggal, jam, BSTHP, customer, PIC,
 * code item). Statistik dashboard dihitung dari tabel ini, bukan dari tabel
 * utama yang bisa berisi ratusan ribu baris.
 */
class ReceivingGoodsRollup
{
    /**
     * Bangun ulang ringkasan. Tanpa argumen = seluruh data; dengan rentang
     * tanggal (Y-m-d) = hanya tanggal-tanggal itu (dipakai setelah import).
     *
     * @return int jumlah baris ringkasan yang dibuat
     */
    public function rebuild(?string $fromDate = null, ?string $toDate = null): int
    {
        @set_time_limit(0);

        $partial = $fromDate !== null && $toDate !== null;

        return DB::transaction(function () use ($partial, $fromDate, $toDate) {
            $delete = DB::table('receiving_goods_rollup');
            if ($partial) {
                $delete->whereBetween('work_date', [$fromDate, $toDate]);
            }
            $delete->delete();

            $where = $partial ? 'date_income_date BETWEEN ? AND ?' : 'date_income IS NOT NULL';
            $bindings = $partial ? [$fromDate, $toDate] : [];

            return DB::affectingStatement(<<<SQL
                INSERT INTO receiving_goods_rollup
                    (work_date, work_hour, bsthp_no, customer, verify_by, code_item, row_count, label_count, qty_sum)
                SELECT
                    date_income_date, date_income_hour, bsthp_no, customer, verify_by, code_item,
                    COUNT(*), COUNT(label_barcode_no), COALESCE(SUM(qty), 0)
                FROM receiving_goods
                WHERE {$where}
                GROUP BY date_income_date, date_income_hour, bsthp_no, customer, verify_by, code_item
            SQL, $bindings);
        });
    }
}
