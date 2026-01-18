<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes, HasCompany;

    public static function booted()
    {
        static::created(function ($quote) {
            dispatch(function () use ($quote) {
                try {
                    $service = new \App\Services\WhatsAppDocumentService();
                    $service->send($quote, 'quote');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Auto WhatsApp Quote Error: " . $e->getMessage());
                }
            })->afterResponse();
        });
    }

    protected $fillable = [
        'company_id',
        'customer_id',
        'quote_number',
        'quote_date',
        'valid_until',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total',
        'notes',
        'terms',
        'created_by',
        'discount_type',
        'discount_value',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'valid_until' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->morphMany(BillingItem::class, 'itemable');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice()
    {
        return $this->hasOne(CustomerInvoice::class);
    }
}
