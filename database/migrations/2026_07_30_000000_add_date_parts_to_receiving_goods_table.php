<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dashboard menjalankan agregasi (GROUP BY / COUNT DISTINCT) memakai
     * DATE_FORMAT(date_income, ...) dan HOUR(date_income). Membungkus kolom
     * dengan fungsi seperti itu membuat MySQL tidak bisa memakai index pada
     * date_income sehingga terjadi full table scan berulang kali (salah satu
     * penyebab utama timeout 60 detik). Migration ini menambahkan kolom
     * generated (STORED) untuk tanggal & jam, lalu mengindexnya, supaya
     * query GROUP BY bisa memakai index langsung tanpa fungsi.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE receiving_goods
                ADD COLUMN date_income_date DATE
                    GENERATED ALWAYS AS (DATE(date_income)) STORED,
                ADD COLUMN date_income_month CHAR(7)
                    GENERATED ALWAYS AS (
                        CONCAT(YEAR(date_income), '-', LPAD(MONTH(date_income), 2, '0'))
                    ) STORED,
                ADD COLUMN date_income_year CHAR(4)
                    GENERATED ALWAYS AS (CAST(YEAR(date_income) AS CHAR(4))) STORED,
                ADD COLUMN date_income_hour TINYINT UNSIGNED
                    GENERATED ALWAYS AS (HOUR(date_income)) STORED
        SQL);

        Schema::table('receiving_goods', function ($table) {
            $table->index('date_income_date');
            $table->index('date_income_month');
            $table->index('date_income_year');
            $table->index('date_income_hour');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receiving_goods', function ($table) {
            $table->dropIndex(['date_income_date']);
            $table->dropIndex(['date_income_month']);
            $table->dropIndex(['date_income_year']);
            $table->dropIndex(['date_income_hour']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE receiving_goods
                DROP COLUMN date_income_date,
                DROP COLUMN date_income_month,
                DROP COLUMN date_income_year,
                DROP COLUMN date_income_hour
        SQL);
    }
};
