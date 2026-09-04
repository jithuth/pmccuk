<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use App\Models\Member;
use App\Models\PaymentHistory;
use App\Models\SponsorOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Str;

class MemberApiController extends Controller
{
    /**
     * Authenticate Member via Member ID / Email / Phone and Password / DOB
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'nullable|string',
        ]);

        $login = trim($request->login);
        
        // Find member by membership_no, email, or mobile_number
        $member = Member::where('membership_id_assigned', $login)
            ->orWhere('email', $login)
            ->orWhere('mobile_number', $login)
            ->orWhere('guid', $login)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'No active membership found matching the provided details.'
            ], 404);
        }

        // Generate dynamic API Bearer Token
        $token = base64_encode($member->id . ':' . $member->guid . ':' . time());

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'member' => [
                    'id' => $member->id,
                    'guid' => $member->guid,
                    'membership_no' => $member->membership_id_assigned ?: 'PENDING',
                    'full_name' => $member->full_name,
                    'email' => $member->email,
                    'mobile_number' => $member->mobile_number,
                    'status' => $member->getMembershipStatusLabel(),
                    'is_active' => $member->isActive(),
                    'expiry_date' => $member->expiry_date,
                    'membership_type' => $member->membership_type,
                    'photo_url' => $member->photo_url,
                ]
            ]
        ]);
    }

    /**
     * Get Authenticated Member Profile & Dependents
     */
    public function profile(Request $request)
    {
        $member = $this->getAuthMember($request);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $member->id,
                'guid' => $member->guid,
                'membership_no' => $member->membership_id_assigned ?: 'PENDING',
                'full_name' => $member->full_name,
                'title' => $member->title,
                'email' => $member->email,
                'mobile_number' => $member->mobile_number,
                'dob' => $member->dob,
                'house_details' => $member->house_details,
                'post_code' => $member->post_code,
                'residing_area' => $member->residing_area,
                'marital_status' => $member->marital_status,
                'spouse_name' => $member->spouse_name,
                'spouse_mobile' => $member->spouse_mobile,
                'emergency_name' => $member->emergency_name,
                'emergency_mobile' => $member->emergency_mobile,
                'membership_type' => $member->membership_type,
                'status' => $member->getMembershipStatusLabel(),
                'is_active' => $member->isActive(),
                'expiry_date' => $member->expiry_date,
                'photo_url' => $member->photo_url,
                'family_photo_url' => $member->family_photo_url,
                'children' => $member->children->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->child_name,
                    'gender' => $c->child_gender,
                    'dob' => $c->child_dob
                ]),
            ]
        ]);
    }

    /**
     * Get Digital Member Identity Card Payload & Signed QR Code
     */
    public function digitalCard(Request $request)
    {
        $member = $this->getAuthMember($request);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $timestamp = time();
        $membershipNo = $member->membership_id_assigned ?: 'PENDING';
        $signature = hash_hmac('sha256', $member->id . ':' . $membershipNo . ':' . $timestamp, config('app.key'));

        $qrPayload = [
            'type' => 'PMCC_MEMBER_ID',
            'member_id' => $member->id,
            'membership_no' => $membershipNo,
            'guid' => $member->guid,
            'status' => $member->getMembershipStatusLabel(),
            'ts' => $timestamp,
            'sig' => substr($signature, 0, 16)
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'card_title' => 'PMCC-UK MEMBER IDENTITY CARD',
                'membership_no' => $membershipNo,
                'full_name' => $member->full_name,
                'membership_type' => strtoupper($member->membership_type ?: 'Standard'),
                'status' => $member->getMembershipStatusLabel(),
                'is_active' => $member->isActive(),
                'expiry_date' => $member->expiry_date ? date('d M Y', strtotime($member->expiry_date)) : 'N/A',
                'photo_url' => $member->photo_url,
                'qr_code_raw' => json_encode($qrPayload),
                'verification_url' => url('/verify-membership?no=' . urlencode($membershipNo)),
                'organization' => 'Plymouth Malayalee Community Club (PMCC-UK)',
            ]
        ]);
    }

    /**
     * Get Member Event Tickets Wallet
     */
    public function tickets(Request $request)
    {
        $member = $this->getAuthMember($request);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $bookings = EventBooking::with('event')
            ->where(function($q) use ($member) {
                if ($member->membership_id_assigned) {
                    $q->where('membership_no', $member->membership_id_assigned);
                }
                if ($member->email) {
                    $q->orWhere('email', $member->email);
                }
            })
            ->orderBy('id', 'desc')
            ->get();

        $ticketList = $bookings->map(function($b) {
            return [
                'id' => $b->id,
                'reference_no' => $b->reference_no,
                'event_title' => $b->event->title ?? 'Event Pass',
                'event_date' => $b->event->event_date ?? null,
                'location' => $b->event->location ?? 'Plymouth, UK',
                'booking_status' => ucfirst($b->booking_status),
                'headcount' => [
                    'adults' => $b->adult_count,
                    'children' => $b->child_count,
                    'students' => $b->student_count,
                    'infants' => $b->infant_count,
                    'total' => ($b->adult_count + $b->child_count + $b->student_count + $b->infant_count)
                ],
                'total_amount' => number_format($b->total_amount, 2),
                'check_in_status' => $b->check_in_at ? 'Attended' : 'Not Checked In',
                'check_in_at' => $b->check_in_at ? $b->check_in_at->format('d M Y, h:i A') : null,
                'qr_code_data' => $b->qr_code_data ?: $b->reference_no,
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $ticketList->count(),
            'data' => $ticketList
        ]);
    }

    /**
     * Get Member Transaction History
     */
    public function transactions(Request $request)
    {
        $member = $this->getAuthMember($request);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $payments = PaymentHistory::where('member_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $eventPurchases = EventBooking::where('email', $member->email)
            ->orWhere('membership_no', $member->membership_id_assigned)
            ->orderBy('id', 'desc')
            ->get();

        $transactions = [];

        foreach ($payments as $p) {
            $transactions[] = [
                'type' => 'Membership Payment',
                'description' => 'Membership Fee (' . ucfirst($p->payment_type) . ')',
                'amount' => '£' . number_format($p->amount, 2),
                'status' => 'Completed',
                'reference' => $p->transaction_ref ?: ('MEM-' . $p->id),
                'date' => $p->created_at ? $p->created_at->format('d M Y, h:i A') : null,
            ];
        }

        foreach ($eventPurchases as $ep) {
            $transactions[] = [
                'type' => 'Event Booking',
                'description' => 'Ticket: ' . ($ep->event->title ?? 'PMCC Event'),
                'amount' => '£' . number_format($ep->total_amount, 2),
                'status' => ucfirst($ep->booking_status),
                'reference' => $ep->reference_no,
                'date' => $ep->created_at ? \Carbon\Carbon::parse($ep->created_at)->format('d M Y, h:i A') : null,
            ];
        }

        return response()->json([
            'success' => true,
            'count' => count($transactions),
            'data' => $transactions
        ]);
    }

    /**
     * Get Active Sponsor Offers & Discounts
     */
    public function offers(Request $request)
    {
        $offers = SponsorOffer::where('status', 'active')->orderBy('id', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $offers
        ]);
    }

    /**
     * Get Upcoming Public Events
     */
    public function events(Request $request)
    {
        $events = Event::orderBy('event_date', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * Helper to authenticate member from Bearer token or Query token
     */
    private function getAuthMember(Request $request): ?Member
    {
        $header = $request->header('Authorization');
        $token = null;

        if ($header && str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
        } elseif ($request->has('token')) {
            $token = $request->query('token');
        }

        if (!$token) {
            return null;
        }

        $decoded = base64_decode($token);
        if (!$decoded || !str_contains($decoded, ':')) {
            return null;
        }

        $parts = explode(':', $decoded);
        $memberId = $parts[0] ?? null;
        $guid = $parts[1] ?? null;

        if (!$memberId || !$guid) {
            return null;
        }

        return Member::where('id', $memberId)->where('guid', $guid)->first();
    }
}
