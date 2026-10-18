<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberConsent extends Model
{
    protected $table = 'member_consents';

    protected $fillable = [
        'member_id',
        'privacy_notice_id',
        'consent_key',
        'lawful_basis',
        'granted',
        'granted_at',
        'revoked_at',
        'ip_address',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function privacyNotice()
    {
        return $this->belongsTo(PrivacyNotice::class, 'privacy_notice_id');
    }
}
