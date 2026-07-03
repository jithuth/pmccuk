<?php namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use App\Models\Member;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function dashboard()
    {
        $events = Event::where('event_date', '>=', now()->subDays(1))->orderBy('event_date', 'asc')->get();
        return view('admin.staff.dashboard', compact('events'));
    }

    public function search(Request $request)
    {
        $q = $request->q;
        
        $bookings = EventBooking::where(function($query) use ($q) {
            $query->where('id', 'LIKE', "%$q%")
                  ->orWhere('full_name', 'LIKE', "%$q%")
                  ->orWhere('email', 'LIKE', "%$q%")
                  ->orWhere('membership_no', 'LIKE', "%$q%");
        })->with('event')->limit(10)->get();

        return response()->json($bookings);
    }

    public function checkIn(Request $request)
    {
        $booking = EventBooking::findOrFail($request->booking_id);
        
        if ($booking->check_in_at) {
            return response()->json(['success' => false, 'message' => 'Already checked in at ' . $booking->check_in_at]);
        }

        if ($booking->booking_status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Booking status is ' . $booking->booking_status . '. Only Approved bookings can check in.']);
        }

        $booking->check_in_at = now();
        $booking->check_in_by = auth('admin')->id();
        $booking->save();

        return response()->json([
            'success' => true, 
            'message' => 'Check-in successful for ' . $booking->full_name,
            'time' => $booking->check_in_at->format('H:i:s')
        ]);
    }

    public function getRecentCheckIns()
    {
        $recents = EventBooking::whereNotNull('check_in_at')
                    ->with('event')
                    ->orderBy('check_in_at', 'desc')
                    ->limit(10)
                    ->get();
                    
        return response()->json($recents);
    }
}
