<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyRequestAction extends Model
{
    protected $table = 'privacy_request_actions';
    public $timestamps = false;

    protected $fillable = [
        'privacy_request_id',
        'admin_id',
        'action_type',
        'description',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(PrivacyRequest::class, 'privacy_request_id');
    }

    public function actor()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
