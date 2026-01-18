<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, HasCompany;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'address',
        'tax_id',
        'notes',
    ];

    public function invoices()
    {
        return $this->hasMany(SupplierInvoice::class);
    }
}
