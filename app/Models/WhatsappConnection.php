<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'phone_number',
        'status',
        'instance_port',
        'qr_code',
        'last_connected_at',
        'last_disconnected_at',
        'error_message',
        'session_data',
        'auto_restart',
    ];

    protected $casts = [
        'session_data' => 'array',
        'last_connected_at' => 'datetime',
        'last_disconnected_at' => 'datetime',
        'auto_restart' => 'boolean',
    ];

    /**
     * Get the company that owns this WhatsApp connection
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if the connection is active
     */
    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    /**
     * Get the instance port or assign a new one
     */
    public function getOrAssignPort(): int
    {
        if (!$this->instance_port) {
            // Assign port based on company_id: 5100 + company_id
            $this->instance_port = 5100 + $this->company_id;
            $this->save();
        }
        
        return $this->instance_port;
    }

    /**
     * Get the session directory path for this company
     */
    public function getSessionDirectory(): string
    {
        return "auth_info_company_{$this->company_id}";
    }
}
