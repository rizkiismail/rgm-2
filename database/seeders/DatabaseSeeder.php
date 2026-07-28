<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Data master Customer & Line (dari file DB20Customer_tanpa_duplikat.xlsx).
        $this->call(CustomerLineSeeder::class);

        // Data master Row Location & Line (dari file Row_Location.xlsx), dipakai untuk
        // mengisi/menyesuaikan kolom Line pada data Scan Out berdasarkan Row Location.
        $this->call(RowLocationSeeder::class);

        // Tidak ada seeder default untuk data transaksi. Gunakan halaman "Upload Data"
        // di web atau perintah `php artisan import:receiving-goods {path}` untuk mengisinya.
    }
}
