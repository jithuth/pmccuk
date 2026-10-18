<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyRequestStatusHistory extends Model
{
    protected $table = 'privacy_request_status_history';
    public $timestamps = false;

    protected $fillable = [
        'privacy_request_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(PrivacyRequest::class, 'privacy_request_id');
    }

    public function changer()
    {
        return $this->belongsTo(Admin::class, 'changed_by');
    }
}
