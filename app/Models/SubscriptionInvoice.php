<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'subscription_id',
        'invoice_number',
        'stripe_invoice_id',
        'amount',
        'tax_amount',
        'vat_amount',
        'total_amount',
        'currency',
        'period_start',
        'period_end',
        'status',
        'due_date',
        'paid_at',
        'payment_method',
        'notes',
        'pdf_path',
        'type',
        'parent_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function parentInvoice()
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'parent_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(SubscriptionInvoice::class, 'parent_id')->where('type', 'credit_note');
    }
}
