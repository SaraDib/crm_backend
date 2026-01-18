<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'duration_months',
        'price',
        'discount_percentage',
        'final_price',
        'customer_limit',
        'user_limit',
        'email_credits',
        'sms_credits',
        'whatsapp_credits',
        'features',
        'is_active',
        'is_trial',
        'trial_days',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'is_trial' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
