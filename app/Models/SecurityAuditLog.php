<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityAuditLog extends Model
{
    protected $table = 'security_audit_logs';
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'actor_type',
        'actor_username',
        'action',
        'resource_type',
        'resource_id',
        'correlation_id',
        'ip_address',
        'user_agent',
        'justification',
        'outcome',
        'payload_summary',
        'approval_reference',
        'created_at',
    ];

    protected $casts = [
        'payload_summary' => 'array',
        'created_at' => 'datetime',
    ];
}
