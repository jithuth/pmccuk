<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyRequestDocument extends Model
{
    protected $table = 'privacy_request_documents';

    protected $fillable = [
        'privacy_request_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'download_token',
        'token_expires_at',
        'downloaded_at',
        'downloaded_ip',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'downloaded_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function request()
    {
        return $this->belongsTo(PrivacyRequest::class, 'privacy_request_id');
    }

    public function isDownloadValid(): bool
    {
        if (empty($this->download_token)) {
            return false;
        }

        if ($this->token_expires_at && $this->token_expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
