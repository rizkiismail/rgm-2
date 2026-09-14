<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dashboard Scan Out menjalankan agregasi (GROUP BY / COUNT DISTINCT) memakai
     * DATE_FORMAT(scan_date, ...) dan HOUR(scan_date). Sama seperti masalah yang
     * pernah terjadi di receiving_goods, membungkus kolom dengan fungsi seperti
     * itu membuat MySQL tidak bisa memakai index pada scan_date sehingga terjadi
     * full table scan berulang kali (buildChartSeries dipanggil 3x + hourly chart
     * = 4x full scan, masing-masing dengan beberapa COUNT DISTINCT) -- salah satu
     * penyebab utama timeout 60/300 detik pada dashboard Scan Out.
     *
     * Migration ini menambahkan kolom generated (STORED) untuk tanggal & jam,
     * lalu mengindexnya, supaya query GROUP BY bisa memakai index langsung
     * tanpa fungsi. Juga menambahkan index pada row_location yang dipakai untuk
     * join ke row_locations dan filter by line.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE scan_outs
                ADD COLUMN scan_date_date DATE
                    GENERATED ALWAYS AS (DATE(scan_date)) STORED,
                ADD COLUMN scan_date_month CHAR(7)
                    GENERATED ALWAYS AS (
                        CONCAT(YEAR(scan_date), '-', LPAD(MONTH(scan_date), 2, '0'))
                    ) STORED,
                ADD COLUMN scan_date_year CHAR(4)
                    GENERATED ALWAYS AS (CAST(YEAR(scan_date) AS CHAR(4))) STORED,
                ADD COLUMN scan_date_hour TINYINT UNSIGNED
                    GENERATED ALWAYS AS (HOUR(scan_date)) STORED
        SQL);

        Schema::table('scan_outs', function ($table) {
            $table->index('scan_date_date');
            $table->index('scan_date_month');
            $table->index('scan_date_year');
            $table->index('scan_date_hour');
            $table->index('row_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_outs', function ($table) {
            $table->dropIndex(['scan_date_date']);
            $table->dropIndex(['scan_date_month']);
            $table->dropIndex(['scan_date_year']);
            $table->dropIndex(['scan_date_hour']);
            $table->dropIndex(['row_location']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE scan_outs
                DROP COLUMN scan_date_date,
                DROP COLUMN scan_date_month,
                DROP COLUMN scan_date_year,
                DROP COLUMN scan_date_hour
        SQL);
    }
};
