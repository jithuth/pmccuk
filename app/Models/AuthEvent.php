<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuthEvent extends Model
{
    public $timestamps = false;
    protected $table = 'auth_events';

    protected $fillable = [
        'event_uuid',
        'correlation_id',
        'actor_type',
        'actor_id',
        'actor_identifier',
        'guard',
        'event_category',
        'action',
        'status',
        'reason_code',
        'ip_address',
        'user_agent',
        'device_hash',
        'device_name',
        'browser',
        'os',
        'location_summary',
        'otp_challenge_id',
        'session_id_ref',
        'risk_score',
        'risk_indicators',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'risk_indicators' => 'array',
        'metadata' => 'array',
        'risk_score' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->event_uuid)) {
                $model->event_uuid = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    public function otpChallenge()
    {
        return $this->belongsTo(OtpChallenge::class, 'otp_challenge_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'actor_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'actor_id');
    }
}
