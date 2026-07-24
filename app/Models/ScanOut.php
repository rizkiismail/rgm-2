<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanOut extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_no',
        'row_location',
        'customer_name',
        'code_item',
        'part_name',
        'part_no',
        'model',
        'barcode',
        'scan_by_nik',
        'scan_by_name',
        'outgoing_no',
        'customer_to',
        'outgoing_type',
        'scan_date',
        'qty',
        'unit',
        'import_batch',
    ];

    protected $casts = [
        'scan_date' => 'datetime',
        'qty' => 'decimal:2',
    ];

    /**
     * Data master customer (nama & line) yang cocok dengan kolom `customer_name` di sini.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_name', 'name');
    }
}
