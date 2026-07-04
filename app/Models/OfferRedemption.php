<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferRedemption extends Model
{
    protected $table = 'offer_redemptions';
    public $timestamps = false;
    protected $fillable = ['offer_id', 'member_id', 'redeemed_at', 'status'];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    public function offer()
    {
        return $this->belongsTo(SponsorOffer::class, 'offer_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
