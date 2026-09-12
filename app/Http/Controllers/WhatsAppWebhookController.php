<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventBooking;
use App\Models\Member;
use App\Models\SponsorOffer;
use App\Services\OpenWaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle inbound messages dispatched from the WhatsApp daemon socket
     */
    public function handle(Request $request)
    {
        // 1. Verify Authorization Bearer Key
        $authHeader = $request->header('Authorization', '');
        $token = trim(str_replace('Bearer ', '', $authHeader));
        if ($token !== OpenWaService::getApiKey()) {
            return response()->json(['error' => 'Unauthorized: Invalid token'], 401);
        }

        $fromJid = $request->input('from'); // e.g. 447901296858@s.whatsapp.net
        $messageText = trim((string) $request->input('message'));
        $pushName = $request->input('pushName', 'Member');

        if (empty($fromJid) || empty($messageText)) {
            return response()->json(['status' => 'ignored']);
        }

        // Clean phone number from JID
        $phone = preg_replace('/[^0-9]/', '', explode('@', $fromJid)[0]);
        $keyword = strtoupper($messageText);

        Log::info("[WhatsApp Inbound] Received '{$keyword}' from {$phone} ({$pushName})");

        // ── Command Routing ──

        // 1. CARD / ID -> Deliver Digital Membership Card
        if (in_array($keyword, ['CARD', 'ID', 'MY CARD', 'MEMBERSHIP', 'MEMBERSHIP CARD'])) {
            $member = Member::where(function ($q) use ($phone) {
                $q->where('mobile_number', 'LIKE', "%{$phone}%")
                  ->orWhere('mobile_number', 'LIKE', '%' . substr($phone, -10) . '%');
            })->where('status', 'active')->first();

            if ($member) {
                OpenWaService::sendText($phone, "🔍 Looking up your active membership profile... Sending your official digital card now!");
                OpenWaService::notifyMemberIdCard($member);
                return response()->json(['status' => 'card_dispatched']);
            } else {
                OpenWaService::sendText($phone, "⚠️ We could not locate an active PMCC-UK membership associated with this phone number (*{$phone}*).\n\nIf you recently registered, your application might be pending committee review.\n👉 Apply or Renew: https://pmccuk.org/membership");
                return response()->json(['status' => 'member_not_found']);
            }
        }

        // 2. TICKET / PASS -> Deliver Upcoming Event Pass
        if (in_array($keyword, ['TICKET', 'TICKETS', 'PASS', 'BOOKING', 'MY TICKET'])) {
            $booking = EventBooking::where(function ($q) use ($phone) {
                $q->where('phone', 'LIKE', "%{$phone}%")
                  ->orWhere('phone', 'LIKE', '%' . substr($phone, -10) . '%');
            })
            ->where('booking_status', 'approved')
            ->orderBy('id', 'desc')
            ->first();

            if ($booking) {
                OpenWaService::sendText($phone, "🎟️ Fetching your confirmed booking pass... Generating ticket with QR code!");
                OpenWaService::notifyEventTicket($booking);
                return response()->json(['status' => 'ticket_dispatched']);
            } else {
                OpenWaService::sendText($phone, "⚠️ No approved event bookings were found for this number (*{$phone}*).\n\nExplore upcoming PMCC events and secure your tickets here:\n👉 https://pmccuk.org/events");
                return response()->json(['status' => 'booking_not_found']);
            }
        }

        // 3. EVENTS -> Show Upcoming Events Schedule
        if (in_array($keyword, ['EVENTS', 'EVENT', 'PROGRAMS', 'PROGRAM'])) {
            $events = Event::where('event_date', '>=', now()->toDateString())
                ->orderBy('event_date', 'asc')
                ->take(5)
                ->get();

            if ($events->count() > 0) {
                $reply = "🎉 *Upcoming PMCC-UK Community Events:*\n\n";
                foreach ($events as $idx => $ev) {
                    $dateStr = date('D, d M Y', strtotime($ev->event_date));
                    $venue = $ev->venue ?: 'Plymouth, Devon';
                    $reply .= ($idx + 1) . ". *{$ev->title}*\n";
                    $reply .= "   📅 {$dateStr}\n";
                    $reply .= "   📍 {$venue}\n";
                    $reply .= "   🔗 Book: https://pmccuk.org/events/{$ev->id}\n\n";
                }
                $reply .= "Reply *TICKET* anytime to retrieve your confirmed passes!\n🌐 https://pmccuk.org";
                OpenWaService::sendText($phone, $reply);
                return response()->json(['status' => 'events_sent']);
            } else {
                OpenWaService::sendText($phone, "ℹ️ There are currently no upcoming events scheduled on the portal. Stay tuned to https://pmccuk.org/events for announcements!");
                return response()->json(['status' => 'no_events']);
            }
        }

        // 4. OFFERS / SPONSORS -> Local Plymouth Discounts
        if (in_array($keyword, ['OFFERS', 'OFFER', 'SPONSORS', 'SPONSOR', 'DISCOUNTS'])) {
            $offers = SponsorOffer::where('is_active', 1)->take(6)->get();
            if ($offers->count() > 0) {
                $reply = "🛍️ *PMCC-UK Member Privilege Discounts in Plymouth:*\n\n";
                foreach ($offers as $idx => $off) {
                    $sponsorName = $off->sponsor_name ?? 'Community Partner';
                    $reply .= ($idx + 1) . ". *{$sponsorName}*\n";
                    $reply .= "   🎁 {$off->title}\n";
                    if (!empty($off->discount_percentage)) {
                        $reply .= "   🏷️ Discount: {$off->discount_percentage}% OFF\n";
                    }
                    $reply .= "   📍 {$off->location}\n\n";
                }
                $reply .= "_Show your PMCC Digital ID Card to redeem these perks._\n👉 Browse all offers: https://pmccuk.org/offers";
                OpenWaService::sendText($phone, $reply);
                return response()->json(['status' => 'offers_sent']);
            } else {
                OpenWaService::sendText($phone, "🛍️ Check out all community partner discounts and sponsor offers on our website:\n👉 https://pmccuk.org/offers");
                return response()->json(['status' => 'offers_generic']);
            }
        }

        // 5. STUDENT -> Student Wing Orientation & Support
        if (in_array($keyword, ['STUDENT', 'STUDENTS', 'UNIVERSITY', 'COLLEGE'])) {
            $reply = "🎓 *Welcome to the PMCC-UK Student Wing!*\n\n" .
                "Are you a student studying in Plymouth (University of Plymouth, Marjon, City College)? We are here to support your UK journey!\n\n" .
                "📌 *Essential Resources:*\n" .
                "• Student Support Hub: https://pmccuk.org/student-corner\n" .
                "• NHS & GP Registration Guide\n" .
                "• Part-time Job Guidance & NI Application\n" .
                "• Indian Grocery Stores & Community Dinners\n\n" .
                "💬 *Connect with our Student Representatives:*\n" .
                "Submit your details at https://pmccuk.org/student-corner to be added to the official student WhatsApp group!\n\n" .
                "Warm regards,\n*PMCC-UK Youth & Student Wing*";

            OpenWaService::sendText($phone, $reply);
            return response()->json(['status' => 'student_sent']);
        }

        // 6. DEFAULT / HELP MENU
        $menu = "🌟 *Welcome to PMCC-UK Interactive WhatsApp!* 🇬🇧\n\n" .
            "Hello *{$pushName}*, reply with any keyword below for instant assistance:\n\n" .
            "👉 *CARD* - Download your Digital Membership Card (PDF)\n" .
            "👉 *TICKET* - Retrieve your Event Admission Ticket (QR Pass)\n" .
            "👉 *EVENTS* - View upcoming community festivals & bookings\n" .
            "👉 *OFFERS* - Explore member discounts at Plymouth restaurants & shops\n" .
            "👉 *STUDENT* - Student Wing orientation & university support\n" .
            "👉 *HELP* - View this quick menu\n\n" .
            "🌐 Official Website: https://pmccuk.org";

        OpenWaService::sendText($phone, $menu);
        return response()->json(['status' => 'menu_sent']);
    }
}
