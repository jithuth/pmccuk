<?php namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventBooking;
use App\Models\Member;
use App\Models\FareRubric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    public function showForm($id)
    {
        $event = Event::with(['rubric.items.category', 'prices.category'])->findOrFail($id);
        
        // If the event uses a Rubric, use those items, otherwise use specific prices
        $pricing = [];
        if ($event->rubric) {
            foreach ($event->rubric->items as $item) {
                $pricing[] = [
                    'category_id' => $item->category_id,
                    'name' => $item->category->name ?? 'Category',
                    'member_price' => $item->member_price,
                    'guest_price' => $item->guest_price,
                ];
            }
        } elseif ($event->prices->count() > 0) {
            foreach ($event->prices as $price) {
                $pricing[] = [
                    'category_id' => $price->category_id,
                    'name' => $price->category->name ?? 'Category',
                    'member_price' => $price->member_price,
                    'guest_price' => $price->guest_price,
                ];
            }
        }

        return view('events.booking_form', compact('event', 'pricing'));
    }

    public function verifyMember(Request $request)
    {
        $id = strtoupper(trim($request->membership_id));
        if ($id && !str_starts_with($id, 'PMCC-')) {
            $id = 'PMCC-' . $id;
        }

        $member = Member::where('membership_id_assigned', $id)->first();

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Membership ID not found']);
        }

        if (!in_array($member->status, ['approved', 'active'])) {
            return response()->json(['success' => false, 'message' => "Membership is {$member->status}. Only Active accounts can book at member rates."]);
        }

        // Mask email for privacy
        $email = $member->email;
        $masked_email = '***';
        if ($email) {
            $parts = explode('@', $email);
            $name = $parts[0];
            $domain = $parts[1];
            $masked_name = substr($name, 0, 1) . '***' . substr($name, -1);
            $masked_email = $masked_name . '@' . $domain;
        }

        Session::put('verifying_member_id', $member->id);

        return response()->json([
            'success' => true,
            'masked_email' => $masked_email
        ]);
    }

    public function sendOTP(Request $request)
    {
        // For members, we use their registered email
        if ($request->has('membership_id') && !empty($request->membership_id)) {
            $id = strtoupper(trim($request->membership_id));
            if (!str_starts_with($id, 'PMCC-')) {
                $id = 'PMCC-' . $id;
            }
            $member = Member::where('membership_id_assigned', $id)->first();
            if (!$member) return response()->json(['success' => false, 'message' => 'Member not found']);
            $email = $member->email;
        } else {
            // For guests
            $request->validate(['email' => 'required|email']);
            $email = $request->email;
        }
        
        $otp = rand(100000, 999999);
        Session::put('booking_otp', $otp);
        Session::put('booking_otp_email', $email);

        try {
            Mail::raw("Your PMCC Event Booking verification code is: $otp", function ($message) use ($email) {
                $message->to($email)
                    ->subject('Event Booking OTP Verification');
            });
            \Illuminate\Support\Facades\Log::info("OTP sent successfully to: " . $email);
            
            // Mask Email for UI privacy
            $parts = explode("@", $email);
            $user_part = $parts[0];
            $domain = $parts[1];
            $masked_user = (strlen($user_part) <= 3) ? $user_part . "***" : substr($user_part, 0, 2) . "***" . substr($user_part, -1);
            $masked_email = $masked_user . "@" . $domain;

            return response()->json(['success' => true, 'message' => 'Verification code sent to ' . $masked_email]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Mail Failure: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Mail delivery failed. Please check your SMTP settings.',
                'debug' => $e->getMessage()
            ]);
        }
    }

    public function verifyOTP(Request $request)
    {
        $otp = $request->otp;
        if ($otp == Session::get('booking_otp')) {
            $member_id = Session::get('verifying_member_id');
            if ($member_id) {
                $member = Member::find($member_id);
                return response()->json([
                    'success' => true,
                    'name' => $member->full_name,
                    'email' => $member->email,
                    'phone' => $member->mobile_number
                ]);
            }
            return response()->json(['success' => true, 'message' => 'OTP Verified Successfully']);
        }
        return response()->json(['success' => false, 'message' => 'Invalid OTP code']);
    }

    public function process(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'full_name' => 'required|string',
            'email' => 'required|email',
            'counts' => 'required|array',
            'otp' => 'required'
        ]);

        // OTP Validation
        if ($request->otp != Session::get('booking_otp') || $request->email != Session::get('booking_otp_email')) {
            return back()->with('error', 'Invalid or expired OTP code.')->withInput();
        }

        // Clear OTP
        Session::forget(['booking_otp', 'booking_otp_email']);

        $event = Event::findOrFail($request->event_id);
        $total_amount = 0;
        $breakdown = [];
        $adults = 0;
        $children = 0;
        $infants = 0;

        foreach ($request->counts as $cat_id => $count) {
            $count = intval($count);
            if ($count > 0) {
                // Determine price based on member status
                $is_member = $request->is_member == '1';
                
                // Fetch price from rubric items or event prices
                // For simplicity, we'll re-fetch or use helper (in production, validate against DB prices)
                // Here we fetch the category name for the breakdown
                $cat_title = \App\Models\FareCategory::find($cat_id)->name ?? 'Item';
                $price = $request->prices[$cat_id] ?? 0; // In a real app, calculate this server-side

                $total_amount += ($count * $price);
                
                $breakdown[] = [
                    'category' => $cat_title,
                    'count' => $count,
                    'price' => $price,
                    'subtotal' => $count * $price
                ];

                // Legacy counting logic
                $name_l = strtolower($cat_title);
                if (strpos($name_l, 'adult') !== false) $adults += $count;
                if (strpos($name_l, 'child') !== false || strpos($name_l, 'kid') !== false) $children += $count;
                if (strpos($name_l, 'infant') !== false) $infants += $count;
            }
        }

        if ($total_amount <= 0) {
            return back()->with('error', 'Please select at least one ticket.')->withInput();
        }

        $booking = EventBooking::create([
            'event_id' => $event->id,
            'membership_no' => $request->is_member == '1' ? (str_starts_with($request->membership_no, 'PMCC-') ? $request->membership_no : 'PMCC-' . $request->membership_no) : 'NON-MEMBER',
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'adult_count' => $adults,
            'child_count' => $children,
            'infant_count' => $infants,
            'attendee_breakdown' => $breakdown,
            'total_amount' => $total_amount,
            'booking_status' => 'pending',
        ]);

        // Send Telegram Notification
        try {
            \App\Services\TelegramService::sendMessage(
                "🎟️ <b>New Event Ticket Booking</b>\n\n" .
                "📅 <b>Event:</b> " . $event->title . "\n" .
                "👤 <b>Booked By:</b> " . $request->full_name . "\n" .
                "📧 <b>Email:</b> " . $request->email . "\n" .
                "📱 <b>Phone:</b> " . $request->phone . "\n" .
                "🎫 <b>Tickets:</b> " . ($adults + $children + $infants) . " (" . $adults . " Adult, " . $children . " Child, " . $infants . " Infant)\n" .
                "💰 <b>Total Paid:</b> £" . number_format($total_amount, 2)
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Telegram failed for event booking: " . $e->getMessage());
        }

        return view('events.booking_confirmation', [
            'booking' => $booking,
            'event' => $event,
            'reference' => "BOOK-" . ($booking->membership_no != 'NON-MEMBER' ? $booking->membership_no : "NM") . "-" . $booking->id
        ]);
    }
    
    public function verifyTicket($reference)
    {
        // Extract ID from reference (format: BOOK-PREFIX-ID)
        $parts = explode('-', $reference);
        $id = end($parts);

        $booking = EventBooking::where('id', $id)->with(['event', 'checker'])->first();
        
        if (!$booking || $booking->reference_no !== $reference) {
            abort(404, 'Invalid Ticket Reference.');
        }

        $isValidState = in_array($booking->booking_status, ['approved']);
        $alreadyScanned = !is_null($booking->check_in_at);

        // If it's a valid ticket and hasn't been scanned yet, mark it as checked in right now
        if ($isValidState && !$alreadyScanned) {
            $booking->check_in_at = now();
            $booking->check_in_status = 'checked_in';
            $booking->check_in_by = auth('admin')->id() ?? null;
            $booking->save();
            
            // Reload the relationship for the view
            $booking->load('checker');
        }

        return view('events.verify_ticket', compact('booking', 'alreadyScanned'));
    }
}
