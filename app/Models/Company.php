<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'email',
        'phone',
        'secondary_phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'tax_id',
        'vat_number',
        'business_registration_number',
        'logo',
        'website',
        'timezone',
        'currency',
        'status',
        'trial_ends_at',
    ];

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_users')
                    ->withPivot(['role_id', 'department', 'job_title', 'is_owner', 'is_active']);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
