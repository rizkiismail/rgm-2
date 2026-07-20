<?php

namespace App\Console\Commands;

use App\Services\ReceivingGoodsImporter;
use Illuminate\Console\Command;

class ImportReceivingGoods extends Command
{
    /**
     * php artisan import:receiving-goods "C:\path\Receiving_Goods_Monitoring.xls" --replace
     */
    protected $signature = 'import:receiving-goods {path : Lokasi file export .xls/.html} {--replace : Hapus semua data lama sebelum import}';

    protected $description = 'Import file export Monitoring Receiving Goods (.xls berformat HTML) ke database';

    public function handle(ReceivingGoodsImporter $importer): int
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
