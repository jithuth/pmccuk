<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyNotice extends Model
{
    protected $table = 'privacy_notices';

    protected $fillable = [
        'version',
        'title',
        'content_markdown',
        'summary_of_changes',
        'published_at',
        'is_active',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function consents()
    {
        return $this->hasMany(MemberConsent::class, 'privacy_notice_id');
    }
}
