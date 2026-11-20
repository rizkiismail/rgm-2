<?php

namespace App\Console\Commands;

use App\Services\ScanOutFacts;
use Illuminate\Console\Command;

class RebuildScanOutFacts extends Command
{
    /**
     * php artisan scanout:rebuild
     */
    protected $signature = 'scanout:rebuild';

    protected $description = 'Bangun ulang tabel fakta dashboard Scan Out dari seluruh data scan_outs';

    public function handle(ScanOutFacts $facts): int
    {
        $this->info('Membangun ulang tabel fakta Scan Out, mohon tunggu...');

        $count = $facts->rebuild();

        $this->info("Selesai. {$count} baris fakta dibuat.");

        return self::SUCCESS;
    }
}
