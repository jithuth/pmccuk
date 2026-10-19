<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OtpChallenge extends Model
{
    protected $table = 'otp_challenges';

    protected $fillable = [
        'challenge_uuid',
        'identifier',
        'member_id',
        'otp_hash',
        'purpose',
        'channel',
        'delivery_status',
        'email_delivery_evidence',
        'whatsapp_delivery_evidence',
        'attempts',
        'max_attempts',
        'resend_available_at',
        'expires_at',
        'verified_at',
        'consumed_at',
        'ip_address',
        'user_agent',
        'correlation_id',
    ];

    protected $casts = [
        'email_delivery_evidence' => 'array',
        'whatsapp_delivery_evidence' => 'array',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'resend_available_at' => 'datetime',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->challenge_uuid)) {
                $model->challenge_uuid = (string) Str::uuid();
            }
        });
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function hasExceededMaxAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }
}
