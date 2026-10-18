<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataRetentionLog extends Model
{
    protected $table = 'data_retention_logs';
    public $timestamps = false;

    protected $fillable = [
        'policy_id',
        'execution_type',
        'records_evaluated',
        'records_disposed',
        'disposal_action',
        'summary',
        'executed_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'records_evaluated' => 'integer',
        'records_disposed' => 'integer',
    ];

    public function policy()
    {
        return $this->belongsTo(DataRetentionPolicy::class, 'policy_id');
    }

    public function executor()
    {
        return $this->belongsTo(Admin::class, 'executed_by');
    }
}
