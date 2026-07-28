<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RowLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'row_location',
        'line',
        'line_label',
    ];

    protected $casts = [
        'line' => 'integer',
    ];
}
