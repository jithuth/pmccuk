<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSmartDecryption;

class RenewalRequest extends Model
{
    use HasSmartDecryption;

    protected $table = 'renewal_requests';
    public $timestamps = false; // Production table is missing updated_at head

    protected $guarded = [];

    protected $casts = [
        // No encrypted casts, the trait handles it dynamically
    ];

    protected $appends = ['photo_url', 'family_photo_url'];

    public function getPhotoUrlAttribute()
    {
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

    public function children()
    {
        return $this->hasMany(RenewalChild::class, 'renewal_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
