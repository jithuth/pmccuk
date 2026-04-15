<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenewalRequest extends Model
{
    protected $table = 'renewal_requests';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'full_name'        => 'encrypted',
        'email'           => 'encrypted',
        'mobile_number'    => 'encrypted',
        'dob'              => 'encrypted',
        'spouse_name'      => 'encrypted',
        'spouse_mobile'    => 'encrypted',
        'spouse_dob'       => 'encrypted',
        'emergency_name'   => 'encrypted',
        'emergency_mobile' => 'encrypted',
        'house_details'    => 'encrypted',
        'post_code'        => 'encrypted',
        'photo'           => 'encrypted',
        'family_photo'     => 'encrypted',
        'bank_account_holder' => 'encrypted',
        'transaction_ref'  => 'encrypted',
        'payment_proof'    => 'encrypted',
    ];

    public function children()
    {
        return $this->hasMany(RenewalChild::class, 'renewal_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
