<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerCategory extends Model
{
    use HasFactory, SoftDeletes, HasCompany;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'color',
        'sort_order',
    ];
}
