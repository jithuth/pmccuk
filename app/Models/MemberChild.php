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

    protected $appends = ['age'];

    public function getAgeAttribute()
    {
        if (isset($this->attributes['age']) && !is_null($this->attributes['age']) && $this->attributes['age'] > 0) {
            return (int) $this->attributes['age'];
        }
        if (empty($this->dob)) return null;
        try {
            return \Illuminate\Support\Carbon::parse($this->dob)->age;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
