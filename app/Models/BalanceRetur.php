<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceRetur extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_no',
        'date_retur',
        'no_retur',
        'rev_no',
        'no_from_customer',
        'customer_name',
        'code_item',
        'part_no',
        'part_name',
        'model',
        'product_status',
        'qty_retur',
        'unit',
        'qty_receiving_part',
        'qty_pending_receiving_part',
        'status_receiving',
        'qty_delivery_part',
        'qty_pending_delivery_part',
        'status_delivery',
        'stock_realtime',
        'final_status',
        'note',
        'pic_ppic_delivery',
        'import_batch',
    ];

    protected $casts = [
        'date_retur' => 'date',
        'qty_retur' => 'decimal:2',
        'qty_receiving_part' => 'decimal:2',
        'qty_pending_receiving_part' => 'decimal:2',
        'qty_delivery_part' => 'decimal:2',
        'qty_pending_delivery_part' => 'decimal:2',
        'stock_realtime' => 'decimal:2',
    ];

    /**
     * Data master customer (nama & line) yang cocok dengan kolom `customer_name` di sini.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_name', 'name');
    }
}
