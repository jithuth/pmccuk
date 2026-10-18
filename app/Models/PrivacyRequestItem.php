<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacyRequestItem extends Model
{
    protected $table = 'privacy_request_items';

    protected $fillable = [
        'privacy_request_id',
        'category',
        'field_name',
        'current_value',
        'proposed_value',
        'status',
        'notes',
    ];

    public function request()
    {
        return $this->belongsTo(PrivacyRequest::class, 'privacy_request_id');
    }
}
