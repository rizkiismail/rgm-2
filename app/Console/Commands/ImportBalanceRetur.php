<?php

namespace App\Console\Commands;

use App\Services\BalanceReturImporter;
use Illuminate\Console\Command;

class ImportBalanceRetur extends Command
{
    /**
     * php artisan import:balance-retur "C:\path\Monitoring_Balance_Retur.xlsx"
     */
    protected $signature = 'import:balance-retur {path : Lokasi file export .xlsx/.xls} {--replace : Hapus semua data lama sebelum import}';

    protected $description = 'Import file export Monitoring Balance Retur (.xlsx) ke database';

    public function handle(BalanceReturImporter $importer): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $this->info('Memproses file, mohon tunggu...');

        $result = $importer->importFromFile(
            $path,
            basename($path).'_'.now()->format('Y-m-d_H-i-s'),
            truncateFirst: (bool) $this->option('replace')
        );

        $this->info("Selesai. {$result['total']} baris terbaca, {$result['inserted']} baris tersimpan/diperbarui, {$result['skipped']} dilewati.");

        return self::SUCCESS;
    }
}
