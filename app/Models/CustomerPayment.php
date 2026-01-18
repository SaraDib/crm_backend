<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPayment extends Model
{
    use HasFactory, SoftDeletes, HasCompany;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_id',
        'payment_number',
        'payment_date',
        'amount',
        'method',
        'reference',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(CustomerInvoice::class, 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
