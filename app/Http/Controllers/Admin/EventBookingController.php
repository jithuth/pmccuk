<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class EventBookingController extends Controller
{
    public function exportPdf(Request $request)
    {
        $query = EventBooking::with(['event', 'checker'])->orderBy('full_name', 'asc');

        if ($request->event_id) {
            $query->where('event_id', $request->event_id);
        }

        // Only export approved by default unless status specified
        $status = $request->input('status', 'approved');
        if ($status != 'all') {
            $query->where('booking_status', $status);
        }

        $bookings = $query->get();
        $event = $request->event_id ? Event::find($request->event_id) : null;

        $pdf = Pdf::loadView('admin.events.pdf_list', compact('bookings', 'event'));
        return $pdf->download('Attendee_List_' . ($event ? str_replace(' ', '_', $event->title) : 'All_Events') . '_' . date('Y-m-d') . '.pdf');
    }

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

        // Prevent duplicate transaction if already approved
        if ($booking->booking_status === 'approved' && $status === 'approved') {
            return back()->with('info', 'Booking is already approved.');
        }

        $booking->booking_status = $status;
        $booking->save();

        // If approved, send the QR code email and record financial transaction
        if ($status === 'approved') {
            // 1. Record in Master Ledger
            \App\Models\FinancialTransaction::create([
                'type' => 'income',
                'category' => 'Event Ticket',
                'amount' => $booking->total_amount,
                'transaction_date' => now()->format('Y-m-d'),
                'description' => "Event Booking: {$booking->full_name} for {$booking->event->title} (Ref: #{$booking->id})",
                'payment_method' => 'Online/Bank',
                'ref_no' => "EVT-{$booking->id}"
            ]);

            // 2. Send Ticket
            try {
                \Illuminate\Support\Facades\Mail::to($booking->email)->send(new \App\Mail\EventTicketMail($booking));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Event Ticket Email Error: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Booking status updated to ' . ucfirst($status) . ($status === 'approved' ? ' and recorded in financials!' : ''));
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
    public function stats()
    {
        $events = Event::orderBy('event_date', 'desc')->get();

        $stats = [];
        foreach ($events as $event) {
            $bookings = EventBooking::where('event_id', $event->id)->get();

            $stats[] = [
                'event' => $event,
                'total_bookings' => $bookings->count(),
                'confirmed_bookings' => $bookings->where('booking_status', 'approved')->count(),
                'total_revenue' => $bookings->where('booking_status', 'approved')->sum('total_amount'),
                'adults' => $bookings->where('booking_status', 'approved')->sum('adult_count'),
                'children' => $bookings->where('booking_status', 'approved')->sum('child_count'),
                'infants' => $bookings->where('booking_status', 'approved')->sum('infant_count'),
                'total_heads' => $bookings->where('booking_status', 'approved')->sum(fn($b) => $b->adult_count + $b->child_count + $b->infant_count)
            ];
        }

        $global = [
            'total_revenue' => EventBooking::where('booking_status', 'approved')->sum('total_amount'),
            'total_bookings' => EventBooking::count(),
            'pending_approval' => EventBooking::where('booking_status', 'pending')->count(),
            'checked_in' => EventBooking::whereNotNull('check_in_at')->count()
        ];

        return view('admin.events.stats', compact('stats', 'global'));
    }
}
