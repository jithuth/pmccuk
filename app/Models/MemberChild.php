<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberChild extends Model
{
    protected $table = 'member_children';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'child_name' => 'encrypted',
        'dob'        => 'encrypted',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
