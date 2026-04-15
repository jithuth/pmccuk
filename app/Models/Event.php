<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';
    public $timestamps = false; // Legacy table uses created_at only

    protected $fillable = [
        'title', 'description', 'event_date', 'location', 
        'image_url', 'rubric_id', 'allow_guest_packages'
    ];

    public function prices()
    {
        return $this->hasMany(EventPrice::class, 'event_id');
    }

    public function bookings()
    {
        return $this->hasMany(EventBooking::class, 'event_id');
    }

    public function rubric()
    {
        return $this->belongsTo(FareRubric::class, 'rubric_id');
    }

    public function getImageUrlAttribute($value)
    {
        if (!$value) return 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200';
        
        if (str_starts_with($value, 'http')) return $value;

        // If it's a standard Laravel upload 'events/...'
        return asset('storage/' . $value);
    }
}
