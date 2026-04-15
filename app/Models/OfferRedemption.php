<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferRedemption extends Model
{
    protected $table = 'offer_redemptions';
    public $timestamps = false;
    protected $fillable = ['offer_id', 'member_id', 'redeemed_at', 'status'];
}
