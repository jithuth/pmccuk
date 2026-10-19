<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityAlert extends Model
{
    protected $table = 'security_alerts';

    protected $fillable = [
        'alert_type',
        'severity',
        'title',
        'details',
        'ip_address',
        'user_type',
        'user_id',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function resolver()
    {
        return $this->belongsTo(Admin::class, 'resolved_by');
    }
}
