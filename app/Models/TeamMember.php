<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $table = 'team_members';
    public $timestamps = false; // Legacy table

    protected $fillable = [
        'name', 'role', 'description', 'image_url', 
        'facebook_url', 'linkedin_url', 'category', 
        'order_no', 'service_years'
    ];

    protected $casts = [
        'name' => 'encrypted',
        'role' => 'encrypted',
    ];
}
