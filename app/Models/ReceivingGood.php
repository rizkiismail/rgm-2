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

    /**
     * date_income_date, date_income_month, date_income_year, date_income_hour
     * adalah generated column (STORED) yang dihitung otomatis oleh database
     * dari date_income. Sengaja TIDAK dimasukkan ke $fillable karena tidak
     * boleh di-set manual; dipakai supaya query GROUP BY di dashboard bisa
     * memakai index langsung tanpa membungkus date_income dengan fungsi.
     */

    /**
     * Data master customer (nama & line) yang cocok dengan kolom `customer` di sini.
     */
    public function customerLine()
    {
        return $this->belongsTo(Customer::class, 'customer', 'name');
    }
}
