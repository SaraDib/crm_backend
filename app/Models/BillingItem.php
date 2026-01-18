<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'itemable_id',
        'itemable_type',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'discount_rate',
        'total',
    ];

    public function itemable()
    {
        return $this->morphTo();
    }
}
