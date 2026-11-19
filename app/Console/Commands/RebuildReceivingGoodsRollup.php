<?php

namespace App\Console\Commands;

use App\Services\ReceivingGoodsRollup;
use Illuminate\Console\Command;

class RebuildReceivingGoodsRollup extends Command
{
    /**
     * php artisan rollup:rebuild
     */
    protected $signature = 'rollup:rebuild';

    protected $description = 'Bangun ulang tabel ringkasan dashboard Receiving Goods dari seluruh data';

    public function handle(ReceivingGoodsRollup $rollup): int
    {
        $this->info('Membangun ulang tabel ringkasan, mohon tunggu...');

        $count = $rollup->rebuild();
        cache()->forever('receiving_goods.data_version', (string) microtime(true));

        $this->info("Selesai. {$count} baris ringkasan dibuat.");

        return self::SUCCESS;
    }
}
