<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;
    protected $table = 'members';

    protected $fillable = [
        'guid', 'title', 'full_name', 'email', 'mobile_number', 'photo', 'family_photo', 
        'dob', 'marital_status', 'spouse_name', 'spouse_mobile', 'spouse_dob', 
        'emergency_name', 'emergency_mobile', 'post_code', 'house_details', 
        'residing_area', 'membership_type', 'status', 'expiry_date', 
        'membership_id_assigned', 'prev_membership_no', 'payment_amount', 
        'transaction_ref', 'payment_proof', 'payment_date', 'bank_account_holder', 
        'consent_given', 'children_below_18', 'children_above_18'
    ];

    protected $casts = [
        'full_name'        => 'encrypted',
        'email'           => 'encrypted',
        'mobile_number'    => 'encrypted',
        'photo'           => 'encrypted',
        'family_photo'     => 'encrypted',
        'dob'              => 'encrypted',
        'spouse_name'      => 'encrypted',
        'spouse_mobile'    => 'encrypted',
        'spouse_dob'       => 'encrypted',
        'emergency_name'   => 'encrypted',
        'emergency_mobile' => 'encrypted',
        'house_details'    => 'encrypted',
        'post_code'        => 'encrypted',
        'prev_membership_no' => 'encrypted',

        'transaction_ref'  => 'encrypted',
        'payment_proof'    => 'encrypted',
        'bank_account_holder' => 'encrypted',
        'consent_given'    => 'boolean',
    ];

    protected $appends = ['photo_url', 'family_photo_url'];

    public function children()
    {
        return $this->hasMany(MemberChild::class, 'member_id');
    }

    public function renewalRequests()
    {
        return $this->hasMany(RenewalRequest::class, 'member_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentHistory::class, 'member_id');
    }

    public function getPhotoUrlAttribute()
    {
        if (!$this->photo) return 'https://placehold.co/400x400?text=No+Photo';
        
        // If it's already a full path or URL
        if (str_starts_with($this->photo, 'http')) return $this->photo;
        
        // If it looks like a legacy photo (no slash)
        if (!str_contains($this->photo, '/')) {
            return asset('storage/photos/' . $this->photo);
        }
        
        return asset('storage/' . $this->photo);
    }

    public function getFamilyPhotoUrlAttribute()
    {
        if (!$this->family_photo) return null;
        if (str_starts_with($this->family_photo, 'http')) return $this->family_photo;
        if (!str_contains($this->family_photo, '/')) {
            return asset('storage/photos/' . $this->family_photo);
        }
        return asset('storage/' . $this->family_photo);
    }
}
