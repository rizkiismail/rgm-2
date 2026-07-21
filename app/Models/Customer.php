<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'line',
    ];

    protected $casts = [
        'line' => 'integer',
    ];

    /**
     * Data receiving goods milik customer ini (dicocokkan lewat nama customer).
     */
    public function receivingGoods()
    {
        return $this->hasMany(ReceivingGood::class, 'customer', 'name');
    }
}
