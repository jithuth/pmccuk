<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityIncidentTimeline extends Model
{
    protected $table = 'security_incident_timeline';
    public $timestamps = false;

    protected $fillable = [
        'security_incident_id',
        'recorded_by',
        'event_type',
        'summary',
        'event_timestamp',
        'created_at',
    ];

    protected $casts = [
        'event_timestamp' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function incident()
    {
        return $this->belongsTo(SecurityIncident::class, 'security_incident_id');
    }

    public function recorder()
    {
        return $this->belongsTo(Admin::class, 'recorded_by');
    }
}
