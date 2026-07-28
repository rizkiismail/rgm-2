<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('row_locations', function (Blueprint $table) {
            $table->id();

            // Kode lokasi rak persis seperti pada kolom "Row" file export Scan Out,
            // mis. "Line 09|Aa|Aa5". Dipakai untuk mencocokkan scan_outs.row_location.
            $table->string('row_location')->unique();

            // Nomor Line (1-14), null untuk lokasi "Transit" / yang belum terpetakan.
            $table->unsignedTinyInteger('line')->nullable();

            // Label Line apa adanya dari file master, mis. "Line 1" atau "Transit".
            $table->string('line_label')->nullable();

            $table->timestamps();

            $table->index('line');
            $table->index('line_label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('row_locations');
    }
};
