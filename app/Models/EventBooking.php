<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSmartDecryption;

class EventBooking extends Model
{
    use HasSmartDecryption;

    protected $table = 'event_bookings';
    public $timestamps = false; 

    protected $fillable = [
        'event_id', 'membership_no', 'adult_count', 'child_count', 
        'infant_count', 'student_count', 'attendee_breakdown', 'email', 'phone', 
        'full_name', 'booking_status', 'total_amount', 'student_doc_path',
        'qr_code_sent', 'qr_code_data', 'check_in_status', 'check_in_at', 'check_in_by'
    ];

    protected $casts = [
        'attendee_breakdown' => 'array',
        'check_in_at' => 'datetime',
        // No encrypted casts, the trait handles it dynamically
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function checker()
    {
        return $this->belongsTo(Admin::class, 'check_in_by');
    }

    public function getStudentCountAttribute()
    {
        if (isset($this->attributes['student_count']) && !is_null($this->attributes['student_count']) && (int)$this->attributes['student_count'] > 0) {
            return (int) $this->attributes['student_count'];
        }

        $count = 0;
        if (is_array($this->attendee_breakdown)) {
            foreach ($this->attendee_breakdown as $item) {
                $cat = strtolower($item['category'] ?? '');
                if (str_contains($cat, 'student')) {
                    $count += intval($item['count'] ?? 0);
                }
            }
        }

        if ($count === 0 && !empty($this->student_doc_path)) {
            $count = 1;
        }

        return $count;
    }

    public function getReferenceNoAttribute()
    {
        $prefix = ($this->membership_no != 'NON-MEMBER') ? $this->membership_no : "NM";
        return "BOOK-" . $prefix . "-" . $this->id;
    }
}
