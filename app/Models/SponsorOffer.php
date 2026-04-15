<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponsorOffer extends Model
{
    protected $table = 'sponsor_offers';
    public $timestamps = false; // Legacy table

    protected $fillable = [
        'admin_id', 'sponsor_name', 'title', 'logo_url', 
        'description', 'offer_details', 'order_no', 
        'is_active', 'is_premium', 'cool_off_hours'
    ];

    protected $casts = [
        'sponsor_name' => 'encrypted',
        'title'        => 'encrypted',
        'description'  => 'encrypted',
        'is_active'   => 'boolean',
        'is_premium'  => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function redemptions()
    {
        return $this->hasMany(OfferRedemption::class, 'offer_id');
    }
}
