<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $table = 'user_devices';

    protected $fillable = [
        'user_type',
        'user_id',
        'device_hash',
        'device_name',
        'browser',
        'os',
        'ip_address',
        'location',
        'is_trusted',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->user_type === 'admin'
            ? $this->belongsTo(Admin::class, 'user_id')
            : $this->belongsTo(Member::class, 'user_id');
    }
}
