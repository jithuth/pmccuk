<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventBooking extends Model
{
    protected $table = 'event_bookings';
    public $timestamps = false; // Legacy table uses created_at only (mostly)

    protected $fillable = [
        'event_id', 'membership_no', 'adult_count', 'child_count', 
        'infant_count', 'attendee_breakdown', 'email', 'phone', 
        'full_name', 'booking_status', 'total_amount', 
        'qr_code_sent', 'qr_code_data', 'check_in_status', 'check_in_at', 'check_in_by'
    ];

    protected $casts = [
        'attendee_breakdown' => 'array',
        'check_in_at' => 'datetime',
        'full_name' => 'encrypted',
        'email'    => 'encrypted',
        'phone'    => 'encrypted',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function checker()
    {
        return $this->belongsTo(Admin::class, 'check_in_by');
    }

    public function getReferenceNoAttribute()
    {
        $prefix = ($this->membership_no != 'NON-MEMBER') ? $this->membership_no : "NM";
        return "BOOK-" . $prefix . "-" . $this->id;
    }
}
