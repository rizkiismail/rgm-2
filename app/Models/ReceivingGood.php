<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivingGood extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_no',
        'date_income',
        'bsthp_date',
        'bsthp_no',
        'verify_no',
        'verify_by',
        'code_item',
        'part_name',
        'part_no',
        'model',
        'qty',
        'unit',
        'bsthp_barcode_no',
        'label_barcode_no',
        'customer',
        'import_batch',
    ];

    protected $casts = [
        'date_income' => 'datetime',
        'bsthp_date' => 'datetime',
        'qty' => 'decimal:2',
    ];
}
