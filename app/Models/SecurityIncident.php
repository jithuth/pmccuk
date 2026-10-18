<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityIncident extends Model
{
    protected $table = 'security_incidents';

    protected $fillable = [
        'reference_number',
        'title',
        'severity',
        'status',
        'affected_data_categories',
        'estimated_affected_members',
        'detected_at',
        'reported_by',
        'lead_investigator',
        'containment_actions',
        'root_cause_analysis',
        'remediation_steps',
        'ico_notifiable',
        'ico_assessment_reasoning',
        'ico_notified_at',
        'ico_reference_number',
        'members_notified',
        'members_notified_at',
        'closed_at',
    ];

    protected $casts = [
        'affected_data_categories' => 'array',
        'detected_at' => 'datetime',
        'ico_notifiable' => 'boolean',
        'ico_notified_at' => 'datetime',
        'members_notified' => 'boolean',
        'members_notified_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(Admin::class, 'reported_by');
    }

    public function investigator()
    {
        return $this->belongsTo(Admin::class, 'lead_investigator');
    }

    public function timeline()
    {
        return $this->hasMany(SecurityIncidentTimeline::class, 'security_incident_id')->orderBy('event_timestamp', 'asc');
    }

    public function isIcoBreachReportingDueSoon(): bool
    {
        if (!$this->ico_notifiable || $this->ico_notified_at !== null) {
            return false;
        }

        // 72-hour ICO reporting rule from detection
        $deadline = $this->detected_at->copy()->addHours(72);
        return now()->lessThanOrEqualTo($deadline);
    }

    public function getIcoDeadlineHoursRemaining(): float
    {
        $deadline = $this->detected_at->copy()->addHours(72);
        return round(now()->diffInMinutes($deadline, false) / 60, 1);
    }
}
