<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes, HasCompany;

    protected $fillable = [
        'company_id',
        'documentable_id',
        'documentable_type',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'user_id',
        'category',
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
