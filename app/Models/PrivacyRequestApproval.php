<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyRequestApproval extends Model
{
    protected $table = 'privacy_request_approvals';

    protected $fillable = [
        'privacy_request_id',
        'approver_id',
        'role_at_approval',
        'approval_type',
        'status',
        'comments',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(PrivacyRequest::class, 'privacy_request_id');
    }

    public function approver()
    {
        return $this->belongsTo(Admin::class, 'approver_id');
    }
}
