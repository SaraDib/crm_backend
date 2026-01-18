<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Subscription extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'plan_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'status',
        'auto_renew',
        'email_credits_used',
        'sms_credits_used',
        'whatsapp_credits_used',
        'customer_count',
        'user_count',
        'canceled_at',
        'cancel_reason',
        'notes',
        'payment_method',
        'payment_reference',
        'paid_at',
        'validated_by',
        'payment_notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'paid_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}
