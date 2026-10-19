<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    protected $table = 'user_sessions';

    protected $fillable = [
        'session_id_hash',
        'user_type',
        'user_id',
        'guard',
        'device_id',
        'ip_address',
        'user_agent',
        'last_activity',
        'is_active',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_activity' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(UserDevice::class, 'device_id');
    }

    public function user()
    {
        return $this->user_type === 'admin'
            ? $this->belongsTo(Admin::class, 'user_id')
            : $this->belongsTo(Member::class, 'user_id');
    }
}
