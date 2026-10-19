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

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function getResolvedAttribute(): bool
    {
        return (bool) $this->is_resolved;
    }

    public function setResolvedAttribute($value): void
    {
        $this->attributes['is_resolved'] = (bool) $value;
    }
}
