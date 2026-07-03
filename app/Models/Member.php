<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSmartDecryption;
use Illuminate\Support\Str;

class Member extends Model
{
    use SoftDeletes, HasSmartDecryption;

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->guid)) {
                $model->guid = (string) Str::uuid();
            }
        });
    }
    
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
        // $this->photo is already intercepted and decrypted by the trait!
        $photo = $this->photo;
        if (!$photo) return 'https://placehold.co/400x400?text=No+Photo';
        if (str_starts_with($photo, 'http')) return $photo;
        
        $path = $photo;
        if (!str_contains($path, '/')) $path = 'photos/' . $path;
        return url('img?p=' . ltrim($path, '/'));
    }

    public function getFamilyPhotoUrlAttribute()
    {
        $photo = $this->family_photo;
        if (!$photo) return null;
        if (str_starts_with($photo, 'http')) return $photo;
        
        $path = $photo;
        if (!str_contains($path, '/')) $path = 'photos/' . $path;
        return url('img?p=' . ltrim($path, '/'));
    }
}
