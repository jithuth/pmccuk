<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPrice extends Model
{
    protected $table = 'event_prices';
    public $timestamps = false;
    protected $fillable = ['event_id', 'category_id', 'member_price', 'guest_price', 'is_guest_visible'];

    public function category()
    {
        return $this->belongsTo(FareCategory::class, 'category_id');
    }
}
