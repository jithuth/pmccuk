<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenewalChild extends Model
{
    protected $table = 'renewal_children';
    public $timestamps = false; // Legacy table has no timestamps

    protected $guarded = [];

    protected $casts = [
        'child_name' => 'encrypted',
        'dob'        => 'encrypted',
    ];

    public function renewal()
    {
        return $this->belongsTo(RenewalRequest::class, 'renewal_id');
    }
}
