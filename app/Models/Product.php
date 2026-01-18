<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Product extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'sku',
        'name',
        'description',
        'unit_price',
        'cost_price',
        'tax_rate',
        'unit',
        'category',
        'is_active',
        'stock_quantity',
        'low_stock_threshold',
        'image',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'low_stock_threshold' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
