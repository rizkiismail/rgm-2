<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Membangun tabel `scan_out_facts` (+ kamus `scan_out_terms`) dari `scan_outs`.
 * Lihat migration create_scan_out_facts_table untuk latar belakangnya.
 */
class ScanOutFacts
{
    public const KIND_CUSTOMER = 1;
    public const KIND_ITEM = 2;
    public const KIND_PIC = 3;
    public const KIND_TYPE = 4;
    public const KIND_LINE = 5;

    /**
     * Bangun ulang. Tanpa argumen = seluruh data; dengan rentang tanggal (Y-m-d) =
     * hanya baris pada tanggal-tanggal itu (dipakai setelah import).
     *
     * @return int jumlah baris fakta pada akhir proses
     */
    public function rebuild(?string $fromDate = null, ?string $toDate = null): int
    {
        @set_time_limit(0);

        $partial = $fromDate !== null && $toDate !== null;
        $range = $partial ? 's.scan_date_date BETWEEN ? AND ?' : 's.scan_date IS NOT NULL';
        $bindings = $partial ? [$fromDate, $toDate] : [];

        DB::transaction(function () use ($partial, $fromDate, $toDate, $range, $bindings) {
            if ($partial) {
                DB::table('scan_out_facts')->whereBetween('d', [$fromDate, $toDate])->delete();
            } else {
                DB::table('scan_out_facts')->delete();
                DB::table('scan_out_terms')->delete();
            }

            $this->fillTerms($range, $bindings);

            // Hash 60-bit dari MD5 (LOWER+RTRIM menyamakan perilaku collation unicode_ci
            // yang dipakai COUNT(DISTINCT) pada tabel utama). Peluang tabrakan diabaikan.
            DB::affectingStatement(<<<SQL
                REPLACE INTO scan_out_facts
                    (id, d, h, cust, item, pic, typ, line_term, line_sort, outg, bc, qty)
                SELECT
                    s.id, s.scan_date_date, s.scan_date_hour,
                    tc.id, ti.id, tp.id, tt.id, tl.id, COALESCE(rl.line, 99),
                    CASE WHEN s.outgoing_no IS NULL THEN NULL
                         ELSE CAST(CONV(LEFT(MD5(LOWER(RTRIM(s.outgoing_no))), 15), 16, 10) AS UNSIGNED) END,
                    CASE WHEN s.barcode IS NULL THEN NULL
                         ELSE CAST(CONV(LEFT(MD5(LOWER(RTRIM(s.barcode))), 15), 16, 10) AS UNSIGNED) END,
                    s.qty
                FROM scan_outs s
                LEFT JOIN scan_out_terms tc ON tc.kind = 1 AND tc.value = s.customer_name
                LEFT JOIN scan_out_terms ti ON ti.kind = 2 AND ti.value = s.code_item
                LEFT JOIN scan_out_terms tp ON tp.kind = 3 AND tp.value = s.scan_by_name
                LEFT JOIN scan_out_terms tt ON tt.kind = 4 AND tt.value = s.outgoing_type
                LEFT JOIN row_locations rl ON rl.row_location = s.row_location
                LEFT JOIN scan_out_terms tl ON tl.kind = 5 AND tl.value = rl.line_label
                WHERE {$range}
            SQL, $bindings);
        });

        // Naikkan versi data supaya cache dashboard Scan Out tidak terpakai lagi.
        Cache::forever('scan_out.data_version', (string) microtime(true));

        return DB::table('scan_out_facts')->count();
    }

    /**
     * Pastikan semua nilai teks yang dipakai baris-baris dalam rentang punya ID di kamus.
     */
    protected function fillTerms(string $range, array $bindings): void
    {
        $dims = [
            [self::KIND_CUSTOMER, 's.customer_name', 'NULL'],
            [self::KIND_ITEM, 's.code_item', 'MIN(s.part_name)'],
            [self::KIND_PIC, 's.scan_by_name', 'MIN(s.scan_by_nik)'],
            [self::KIND_TYPE, 's.outgoing_type', 'NULL'],
        ];

        foreach ($dims as [$kind, $column, $extra]) {
            DB::insert(<<<SQL
                INSERT INTO scan_out_terms (kind, value, extra)
                SELECT {$kind}, {$column}, {$extra}
                FROM scan_outs s
                WHERE {$range} AND {$column} IS NOT NULL
                GROUP BY {$column}
                ON DUPLICATE KEY UPDATE extra = IF(
                    VALUES(extra) IS NOT NULL AND (scan_out_terms.extra IS NULL OR VALUES(extra) < scan_out_terms.extra),
                    VALUES(extra), scan_out_terms.extra)
            SQL, $bindings);
        }

        DB::insert(<<<'SQL'
            INSERT IGNORE INTO scan_out_terms (kind, value)
            SELECT 5, line_label FROM row_locations WHERE line_label IS NOT NULL GROUP BY line_label
        SQL);
    }
}
