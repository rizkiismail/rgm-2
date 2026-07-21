<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class CustomerLineSeeder extends Seeder
{
    /**
     * Data master Customer & Line, diambil dari file
     * "DB20Customer_tanpa_duplikat.xlsx". Setiap baris: [nama_customer, nomor_line].
     */
    protected array $customers = [
            ['ANTIKA RAYA, PT.', 1],
            ['ARISTON MITRA NIAGA, PT.', 1],
            ['ASAHI INDONESIA, PT.', 1],
            ['FAMINDO JAYA', 1],
            ['GANESHA YUNIOR', 1],
            ['H. ADE RAMDAN TRIYATNA', 1],
            ['HANKEN INDONESIA, PT', 1],
            ['HIDAYAT JAYA RUBBER', 1],
            ['HUSEIN RUBBER', 1],
            ['HUTAN KARET UTAMA, CV.', 1],
            ['INTERUB SUPPLY INDONESIA, PT.', 1],
            ['JAMAL', 1],
            ['MUTIA RAHMAWATI', 1],
            ['NAWASENA METAL INDONESIA, PT', 1],
            ['NISHIKAWA KARYA INDONESIA, PT.', 1],
            ['PANCAR JAYA', 1],
            ['SERVVO FIRE INDONESIA, PT.', 1],
            ['SHIMADA KARYA INDONESIA, PT.', 1],
            ['SULAEMAN PUTRA JAYA', 1],
            ['SUMBER JAYA, PD.', 1],
            ['ASAHI BEST BASE INDONESIA, PT.', 2],
            ['EWINDO, PT.', 2],
            ['SURYAGRAHA SINAR CEMERLANG, PT', 2],
            ['SURYAJI', 2],
            ['ROKI INDONESIA, PT.', 3],
            ['ARTHA UTAMA PLASINDO, PT', 4],
            ['ASTRA HONDA MOTOR, PT', 4],
            ['DENSO MANUFACTURING INDONESIA, PT.', 4],
            ['GS ELECTECH INDONESIA, PT', 4],
            ['KAWASAKI MOTOR INDONESIA, PT.', 4],
            ['API INTERNATIONAL INDONESIA, PT.', 5],
            ['DAE YOUNG APEX INDONESIA, PT.', 5],
            ['DIAMOND ELECTRIC MFG INDONESIA, PT.', 5],
            ['GARUDA METAL UTAMA, PT.', 5],
            ['INDOPRIMA GEMILANG, PT.', 5],
            ['KING PLASTIC, PT.', 5],
            ['SHINTO KOGYO INDONESIA, PT.', 5],
            ['TOYO DENSO INDONESIA, PT.', 5],
            ['WANMEI ABADI INDUSTRI, PT.', 5],
            ['GALIH SEKAR SAKTI, PT.', 6],
            ['INDONESIA NIPPON SEIKI, PT.', 6],
            ['INDONESIA STANLEY ELECTRIC, PT.', 6],
            ['MITSUBA INDONESIA, PT.', 6],
            ['NIPPON HIKARI INDONESIA, PT.', 6],
            ['RODA PRIMA LANCAR, PT.', 6],
            ['SELAMAT SEMPURNA TBK, PT.', 6],
            ['SINAR ALUM SARANA, PT', 6],
            ['YASUNLI ABADI UTAMA PLASTIK, PT.', 6],
            ['YONGAN INDO MAJU, PT.', 6],
            ['ASTRA KOMPONEN INDONESIA, PT.', 7],
            ['CIPTA MANDIRI WIRASAKTI, PT.', 7],
            ['JAEIL INDONESIA, PT.', 7],
            ['SANDEN INDONESIA, PT.', 7],
            ['USUI INTERNATIONAL INDONESIA, PT.', 7],
            ['ASTRA VISTEON INDONESIA, PT.', 8],
            ['ASTRA VISTEON VIETNAM CO., LTD.', 8],
            ['ERAN PLASTINDO UTAMA, PT.', 8],
            ['KAYABA INDONESIA, PT', 8],
            ['MEIWA INDONESIA, PT.', 8],
            ['NANDYA KARYA PERKASA, PT.', 8],
            ['TARA CITRA KUSUMA, PT.', 8],
            ['TARA KUSUMA INDAH, PT.', 8],
            ['AKEBONO BRAKE ASTRA INDONESIA, PT.', 9],
            ['CHUHATSU INDONESIA, PT.', 9],
            ['DYNAPLAST, PT.', 9],
            ['SUZUKI INDOMOBIL MOTOR, PT.', 9],
            ['SUZUKI INDOMOBIL SALES, PT.', 9],
            ['ASTRA DAIHATSU MOTOR, PT.', 10],
            ['ASTRA OTOPARTS TBK, PT (NUSAMETAL)', 10],
            ['BERDIKARI METAL & ENGINEERING, PT.', 10],
            ['KRAMA YUDHA TIGA BERLIAN MOTORS, PT.', 10],
            ['KYORAKU BLOWMOLDING INDONESIA, PT.', 10],
            ['MITSUBISHI FUSO TRUCK & BUS CORPORATION', 10],
            ['MITSUBISHI KRAMA YUDHA MOTORS AND MANUFACTURING, PT', 10],
            ['MORADON BERLIAN SAKTI, PT.', 10],
            ['TOYOTA MOTOR MANUFACTURING INDONESIA, PT.', 10],
            ['ASTRA JUOKU INDONESIA, PT.', 11],
            ['AUTOPLASTIK INDONESIA, PT.', 11],
            ['CHEMCO HARAPAN NUSANTARA, PT.', 11],
            ['DHARMA MULTIPLAST SOLUTIONS INDONESIA, PT.', 11],
            ['FUJI SEAT INDONESIA, PT.', 11],
            ['INDOPLAT PERKASA PURNAMA, PT.', 11],
            ['ISUZU ASTRA MOTOR INDONESIA, PT.', 11],
            ['JONAN INDONESIA, PT.', 11],
            ['SAKAE RIKEN INDONESIA, PT.', 11],
            ['ASAHIMAS FLAT GLASS TBK, PT.', 12],
            ['BANSHU ELECTRIC INDONESIA, PT.', 12],
            ['HINO MOTORS MANUFACTURING INDONESIA, PT.', 12],
            ['HINO MOTORS SALES INDONESIA, PT.', 12],
            ['INDONESIA KOITO, PT.', 12],
            ['MITSUBA AUTOMOTIVE PARTS INDONESIA, PT.', 12],
            ['TRIX INDONESIA, PT.', 12],
            ['VALEO AC INDONESIA, PT.', 12],
            ['VELASTO INDONESIA, PT', 12],
            ['KOMATSU INDONESIA KBN PLANT, PT.', 13],
            ['KOMATSU INDONESIA, PT.', 13],
            ['KOMATSU MARKETING & SUPPORT INDONESIA, PT', 13],
            ['ARMETA KREASI MANDIRI, PT.', 14],
            ['B.S. INDONESIA, PT.', 14],
            ['CASUARINA HARNESSINDO, PT.', 14],
            ['CHANDRA NUGERAHCIPTA, PT', 14],
            ['DHARMA CONTROLCABLE INDONESIA, PT.', 14],
            ['DHARMA ELECTRINDO MANUFACTURING, PT.', 14],
            ['MADA WIKRI TUNGGAL, PT.', 14],
            ['MULIAGLASS, PT.', 14],
            ['T.RAD INDONESIA , PT', 14],
            ['YASUFUKU INDONESIA, PT.', 14],
            ['AUTOCOMP SYSTEMS INDONESIA, PT.', 15],
            ['DHARMA POLIMETAL, PT.', 16],
            ['DHARMA POLIPLAST, PT', 16],
            ['FTS AUTOMOTIVE INDONESIA, PT.', 16],
            ['INJEKSI PLASTIK PASIFIK, PT.', 16],
            ['MECOINDO, PT.', 16],
            ['NITTO MATERIALS INDONESIA, PT.', 16],
            ['OPUCO INDONESIA, PT', 16],
            ['TSUANG HINE INDUSTRIAL, PT.', 16],
            ['HI-LEX INDONESIA, PT.', 17],
    ];

    public function run(): void
    {
        $now = now();

        $rows = collect($this->customers)->map(function ($row) use ($now) {
            return [
                'name' => $row[0],
                'line' => $row[1],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('customers')->upsert($chunk, ['name'], ['line', 'updated_at']);
        }

        $this->command?->info(count($this->customers).' data customer & line berhasil di-seed.');
    }
}
