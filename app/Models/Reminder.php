<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_id',
        'supplier_invoice_id',
        'device_id',
        'title',
        'description',
        'type',
        'priority',
        'reminder_date',
        'reminder_time',
        'status',
        'completed_at',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'reminder_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function supplierInvoice()
    {
        return $this->belongsTo(SupplierInvoice::class);
    }
}
