<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class EventBookingController extends Controller
{
    private function buildQuery(Request $request)
    {
        $query = EventBooking::with(['event', 'checker']);

        if ($request->event_id) {
            $query->where('event_id', $request->event_id);
        }

        // Filter status (default to pending & approved combined if not explicitly 'all')
        $status = $request->input('status');
        if ($status === 'pending_and_approved' || $status === 'pending_approved' || empty($status)) {
            $query->whereIn('booking_status', ['pending', 'approved']);
        } elseif ($status !== 'all') {
            $query->where('booking_status', $status);
        }

        // Filter by check-in status
        if ($request->check_in === 'checked_in') {
            $query->whereNotNull('check_in_at');
        } elseif ($request->check_in === 'not_checked') {
            $query->whereNull('check_in_at');
        }

        // Filter by ticket type
        if ($request->ticket_type === 'student') {
            $query->where(function($q) {
                $q->where('student_count', '>', 0)
                  ->orWhereNotNull('student_doc_path');
            });
        } elseif ($request->ticket_type === 'adult') {
            $query->where('adult_count', '>', 0);
        } elseif ($request->ticket_type === 'child') {
            $query->where('child_count', '>', 0);
        } elseif ($request->ticket_type === 'infant') {
            $query->where('infant_count', '>', 0);
        }

        return $query;
    }

    public function exportPdf(Request $request)
    {
        $bookings = $this->buildQuery($request)->orderBy('full_name', 'asc')->get();
        $event = $request->event_id ? Event::find($request->event_id) : null;

        $pdf = Pdf::loadView('admin.events.pdf_list', compact('bookings', 'event'));
        return $pdf->download('Attendee_List_' . ($event ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $event->title) : 'All_Events') . '_' . date('Y-m-d') . '.pdf');
    }

    public function exportCsv(Request $request)
    {
        $bookings = $this->buildQuery($request)->orderBy('full_name', 'asc')->get();
        $event = $request->event_id ? Event::find($request->event_id) : null;
        $fileName = 'Attendee_List_' . ($event ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $event->title) : 'All_Events') . '_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($bookings) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($file, [
                'Booking ID', 'Event Name', 'Event Date', 'Attendee Full Name', 
                'Email', 'Phone', 'Membership No', 'Booking Status', 
                'Adults (A)', 'Children (C)', 'Infants (I)', 'Students (S)', 
                'Total Heads', 'Total Amount (£)', 'Check-In Status', 'Check-In Time'
            ]);

            foreach ($bookings as $b) {
                fputcsv($file, [
                    '#' . $b->id,
                    $b->event->title ?? 'N/A',
                    $b->event->event_date ?? 'N/A',
                    $b->full_name,
                    $b->email,
                    $b->phone,
                    $b->membership_no ?: 'NON-MEMBER',
                    ucfirst($b->booking_status),
                    $b->adult_count,
                    $b->child_count,
                    $b->infant_count,
                    $b->student_count,
                    ($b->adult_count + $b->child_count + $b->infant_count + $b->student_count),
                    number_format($b->total_amount, 2),
                    $b->check_in_at ? 'Checked In' : 'Not Checked In',
                    $b->check_in_at ? \Carbon\Carbon::parse($b->check_in_at)->format('Y-m-d H:i:s') : 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function index(Request $request)
    {
        $allMatchingBookings = $this->buildQuery($request)->get();
        
        $summary = [
            'total_bookings' => $allMatchingBookings->count(),
            'total_heads' => $allMatchingBookings->sum(fn($b) => $b->adult_count + $b->child_count + $b->infant_count + $b->student_count),
            'adults' => $allMatchingBookings->sum('adult_count'),
            'children' => $allMatchingBookings->sum('child_count'),
            'infants' => $allMatchingBookings->sum('infant_count'),
            'students' => $allMatchingBookings->sum(fn($b) => $b->student_count),
            'checked_in_heads' => $allMatchingBookings->whereNotNull('check_in_at')->sum(fn($b) => $b->adult_count + $b->child_count + $b->infant_count + $b->student_count),
            'pending_heads' => $allMatchingBookings->whereNull('check_in_at')->sum(fn($b) => $b->adult_count + $b->child_count + $b->infant_count + $b->student_count),
        ];

        $bookings = $this->buildQuery($request)->orderBy('id', 'desc')->paginate(20)->withQueryString();
        $events = Event::orderBy('event_date', 'desc')->get();

        return view('admin.events.bookings', compact('bookings', 'events', 'summary'));
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
        $booking->update($request->only(['full_name', 'email', 'phone', 'adult_count', 'child_count', 'infant_count', 'student_count']));

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
                'students' => $bookings->where('booking_status', 'approved')->sum(fn($b) => $b->student_count),
                'total_heads' => $bookings->where('booking_status', 'approved')->sum(fn($b) => $b->adult_count + $b->child_count + $b->infant_count + $b->student_count)
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
