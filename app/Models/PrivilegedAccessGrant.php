<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivilegedAccessGrant extends Model
{
    protected $table = 'privileged_access_grants';

    protected $fillable = [
        'admin_id',
        'target_role_slug',
        'justification',
        'approved_by',
        'granted_at',
        'expires_at',
        'revoked_at',
        'status',
        'ip_address',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function approver()
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
