<?php namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Http\Request;

class EventBookingController extends Controller
{
    public function index(Request $request)
    {
        $query = EventBooking::with(['event', 'checker'])->orderBy('id', 'desc');

        if ($request->event_id) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->status) {
            $query->where('booking_status', $request->status);
        }

        // Filter by check-in status
        if ($request->check_in === 'checked_in') {
            $query->whereNotNull('check_in_at');
        } elseif ($request->check_in === 'not_checked') {
            $query->whereNull('check_in_at');
        }

        $bookings = $query->paginate(20);
        $events = Event::orderBy('event_date', 'desc')->get();

        return view('admin.events.bookings', compact('bookings', 'events'));
    }

    public function updateStatus($id, $status)
    {
        $booking = EventBooking::findOrFail($id);
        $booking->booking_status = $status;
        $booking->save();

        // If approved, send the QR code email
        if ($status === 'confirmed') {
            try {
                \Illuminate\Support\Facades\Mail::to($booking->email)->send(new \App\Mail\EventTicketMail($booking));
            } catch (\Exception $e) {
                // Log error but don't prevent approval
                \Illuminate\Support\Facades\Log::error('Event Ticket Email Error: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Booking status updated to ' . ucfirst($status) . ($status === 'confirmed' ? ' and ticket sent!' : ''));
    }

    public function resendTicket($id)
    {
        $booking = EventBooking::findOrFail($id);
        
        try {
            \Illuminate\Support\Facades\Mail::to($booking->email)->send(new \App\Mail\EventTicketMail($booking));
            return back()->with('success', 'Ticket email has been resent to ' . $booking->email);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to resend email: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $booking = EventBooking::findOrFail($id);
        $events = Event::all();
        return view('admin.events.bookings_edit', compact('booking', 'events'));
    }

    public function update(Request $request, $id)
    {
        $booking = EventBooking::findOrFail($id);
        $booking->update($request->only(['full_name', 'email', 'phone', 'adult_count', 'child_count', 'infant_count']));
        
        return redirect()->route('admin.events.bookings')->with('success', 'Booking details updated successfully.');
    }

    public function destroy($id)
    {
        $booking = EventBooking::findOrFail($id);
        $booking->delete();

        return back()->with('success', 'Booking deleted successfully');
    }
}
