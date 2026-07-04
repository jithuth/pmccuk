<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = "activity_logs";
    public $timestamps = false;

    protected $fillable = [
        "admin_id", "admin_username", "user_type", "action", "details", "ip_address", "user_agent", "browser", "os", "device"
    ];

    protected $casts = [
        "action" => "encrypted",
        "details" => "encrypted",
        "created_at" => "datetime",
    ];
}
