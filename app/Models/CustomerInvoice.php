<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerInvoice extends Model
{
    use HasFactory, SoftDeletes, HasCompany;
    
    public static function booted()
    {
        static::created(function ($invoice) {
            dispatch(function () use ($invoice) {
                try {
                    $service = new \App\Services\WhatsAppDocumentService();
                    $service->send($invoice, $invoice->type ?? 'invoice');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Auto WhatsApp Error: " . $e->getMessage());
                }
            })->afterResponse();
        });
    }

    protected $fillable = [
        'company_id',
        'customer_id',
        'quote_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'paid_amount',
        'balance',
        'notes',
        'terms',
        'created_by',
        'type',
        'parent_id',
        'discount_type',
        'discount_value',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function items()
    {
        return $this->morphMany(BillingItem::class, 'itemable');
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class, 'invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentInvoice()
    {
        return $this->belongsTo(CustomerInvoice::class, 'parent_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(CustomerInvoice::class, 'parent_id')->where('type', 'credit_note');
    }
}
