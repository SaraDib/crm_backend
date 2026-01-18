<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'password',
        'user_type',
        'phone',
        'avatar',
        'timezone',
        'language',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_enabled' => 'boolean',
    ];

    protected $appends = ['all_roles', 'all_permissions'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users')
                    ->withPivot(['role_id', 'department', 'job_title', 'is_owner', 'is_active']);
    }

    public function getAllRolesAttribute()
    {
        if ($this->user_type === 'system') {
            return $this->roles;
        }

        // For company users, they might have multiple companies with different roles.
        // We take the roles from all associated companies.
        $roleIds = DB::table('company_users')
            ->where('user_id', $this->id)
            ->pluck('role_id');

        return Role::whereIn('id', $roleIds)->get();
    }

    public function getAllPermissionsAttribute()
    {
        $roles = $this->all_roles;
        $permissionIds = DB::table('role_permissions')
            ->whereIn('role_id', $roles->pluck('id'))
            ->pluck('permission_id')
            ->unique();

        return Permission::whereIn('id', $permissionIds)->get();
    }

    public function isSystemAdmin(): bool
    {
        return $this->user_type === 'system' && $this->roles()->where('slug', 'super-admin')->exists();
    }

    public function sentMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(ChatMessage::class, 'receiver_id');
    }

    /**
     * Get unique users who have chatted with this user.
     */
    public function chats()
    {
        $sentTo = $this->sentMessages()->pluck('receiver_id');
        $receivedFrom = $this->receivedMessages()->pluck('sender_id');
        
        $userIds = $sentTo->merge($receivedFrom)->unique()->filter(fn($id) => $id != $this->id);
        
        return User::whereIn('id', $userIds)->get();
    }
}
