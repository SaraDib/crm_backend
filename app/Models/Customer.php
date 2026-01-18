<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CustomerCategory;
use App\Models\User;

class Customer extends Model
{
    use HasFactory, SoftDeletes, HasCompany;

    protected $fillable = [
        'company_id',
        'customer_number',
        'type',
        'salutation',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'secondary_phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'latitude',
        'longitude',
        'tax_status',
        'vat_number',
        'tax_id',
        'category_id',
        'source',
        'credit_limit',
        'balance',
        'currency',
        'payment_terms',
        'discount_rate',
        'assigned_to',
        'status',
        'lead_score',
        'last_contacted_at',
        'last_purchase_at',
        'total_purchases',
        'total_paid',
        'notes',
        'tags',
        'custom_fields',
    ];

    protected $casts = [
        'tags' => 'array',
        'custom_fields' => 'array',
        'last_contacted_at' => 'datetime',
        'last_purchase_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(CustomerCategory::class, 'category_id');
    }

    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
