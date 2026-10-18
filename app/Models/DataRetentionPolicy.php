<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataRetentionPolicy extends Model
{
    protected $table = 'data_retention_policies';

    protected $fillable = [
        'data_category',
        'purpose',
        'lawful_basis',
        'retention_period_days',
        'retention_trigger',
        'responsible_owner',
        'disposal_action',
        'legal_hold',
        'legal_hold_reason',
        'is_active',
    ];

    protected $casts = [
        'legal_hold' => 'boolean',
        'is_active' => 'boolean',
        'retention_period_days' => 'integer',
    ];

    public function logs()
    {
        return $this->hasMany(DataRetentionLog::class, 'policy_id')->orderBy('created_at', 'desc');
    }
}
