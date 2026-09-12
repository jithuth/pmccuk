<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WhatsAppScheduledBroadcast extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_scheduled_broadcasts';

    protected $fillable = [
        'title',
        'audience',
        'selected_executives',
        'custom_numbers',
        'message',
        'attachments',
        'recipients_data',
        'scheduled_at',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'error_log',
        'executed_at',
        'created_by'
    ];

    protected $casts = [
        'selected_executives' => 'array',
        'attachments' => 'array',
        'recipients_data' => 'array',
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    /**
     * Scope for pending broadcasts due to be processed
     */
    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', Carbon::now());
            });
    }

    /**
     * Scope for all pending broadcasts
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
