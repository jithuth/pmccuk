<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Traits\HasSmartDecryption;

class PrivacyRequest extends Model
{
    use HasSmartDecryption;

    protected $table = 'privacy_requests';

    public array $encryptable = [
        'requester_name', 'requester_email', 'requester_phone', 'details', 'decision_reasoning',
    ];

    protected $fillable = [
        'reference_number',
        'member_id',
        'requester_name',
        'requester_email',
        'requester_phone',
        'request_type',
        'details',
        'identity_verified',
        'identity_verified_at',
        'identity_verification_method',
        'received_at',
        'statutory_deadline',
        'extended_deadline',
        'extension_reason',
        'status',
        'decision',
        'decision_reasoning',
        'applicable_exemptions',
        'assigned_to',
        'completed_at',
        'correlation_id',
    ];

    protected $casts = [
        'identity_verified' => 'boolean',
        'identity_verified_at' => 'datetime',
        'received_at' => 'datetime',
        'statutory_deadline' => 'date',
        'extended_deadline' => 'date',
        'completed_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function assignee()
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function items()
    {
        return $this->hasMany(PrivacyRequestItem::class, 'privacy_request_id');
    }

    public function actions()
    {
        return $this->hasMany(PrivacyRequestAction::class, 'privacy_request_id')->orderBy('created_at', 'desc');
    }

    public function approvals()
    {
        return $this->hasMany(PrivacyRequestApproval::class, 'privacy_request_id');
    }

    public function documents()
    {
        return $this->hasMany(PrivacyRequestDocument::class, 'privacy_request_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(PrivacyRequestStatusHistory::class, 'privacy_request_id')->orderBy('created_at', 'asc');
    }

    public function getEffectiveDeadline(): Carbon
    {
        if ($this->extended_deadline) {
            return Carbon::parse($this->extended_deadline)->endOfDay();
        }

        return Carbon::parse($this->statutory_deadline)->endOfDay();
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, ['completed', 'partially_fulfilled', 'refused', 'withdrawn'])) {
            return false;
        }

        return $this->getEffectiveDeadline()->isPast();
    }

    public function getDaysRemaining(): int
    {
        $deadline = $this->getEffectiveDeadline();
        return (int) now()->diffInDays($deadline, false);
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'submitted' => 'info',
            'identity_review' => 'warning',
            'data_discovery' => 'primary',
            'legal_review' => 'secondary',
            'approval_pending' => 'warning',
            'fulfilment' => 'primary',
            'refused' => 'danger',
            'withdrawn' => 'dark',
            'on_hold' => 'secondary',
            default => 'light',
        };
    }
}
