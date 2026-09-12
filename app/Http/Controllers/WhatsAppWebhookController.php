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

        $fromJid = trim((string) $request->input('from')); // e.g. 275767166550158@lid or 447901296858@s.whatsapp.net
        $messageText = trim((string) $request->input('message'));
        $pushName = trim((string) $request->input('pushName', 'Member'));
        $detectedPhone = $request->input('phone');

        if (empty($fromJid) || empty($messageText)) {
            return response()->json(['status' => 'ignored']);
        }

        // Clean phone number if from standard @s.whatsapp.net
        $phone = $detectedPhone;
        if (empty($phone) && !str_contains($fromJid, '@lid')) {
            $phone = preg_replace('/[^0-9]/', '', explode('@', $fromJid)[0]);
        }

        $rawUpper = strtoupper(trim($messageText));
        $upperText = ltrim($rawUpper, '/#!.');
        Log::info("[WhatsApp Inbound] Received '{$upperText}' (raw: '{$messageText}') from {$fromJid} (Phone: {$phone}, PushName: {$pushName})");

        // Check for explicit membership ID or phone number inside the text (e.g. "CARD PMCC-104" or "CARD 07901296858")
        $explicitRef = null;
        if (preg_match('/(PMCC-?\d+|\b\d{6,13}\b)/i', $messageText, $matches)) {
            $explicitRef = trim($matches[0]);
        }

        // ── Command Routing ──

        // 1. CARD / ID -> Deliver Digital Membership Card
        if (str_starts_with($upperText, 'CARD') || str_starts_with($upperText, 'ID') || in_array($upperText, ['MY CARD', 'MEMBERSHIP', 'MEMBERSHIP CARD'])) {
            $member = null;

            // Tier 1: Search by explicit reference in message
            if ($explicitRef) {
                $cleanRef = preg_replace('/[^0-9]/', '', $explicitRef);
                $member = Member::where(function ($q) use ($explicitRef, $cleanRef) {
                    $q->where('membership_id_assigned', 'LIKE', "%{$explicitRef}%")
                      ->orWhere('id', $explicitRef)
                      ->orWhere('mobile_number', 'LIKE', "%{$cleanRef}%");
                })->where('status', 'active')->first();
            }

            // Tier 2: Search by detected phone number
            if (!$member && !empty($phone)) {
                $cleanPn = preg_replace('/[^0-9]/', '', $phone);
                $last10 = substr($cleanPn, -10);
                $member = Member::where(function ($q) use ($cleanPn, $last10) {
                    $q->where('mobile_number', 'LIKE', "%{$cleanPn}%")
                      ->orWhere('mobile_number', 'LIKE', "%{$last10}%");
                })->where('status', 'active')->first();
            }

            // Tier 3: Search by WhatsApp display name (pushName)
            if (!$member && !empty($pushName) && strtolower($pushName) !== 'member') {
                $cleanPush = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $pushName));
                if (strlen($cleanPush) >= 3) {
                    $words = array_filter(explode(' ', $cleanPush), fn($w) => strlen($w) >= 3);
                    $member = Member::where(function ($q) use ($cleanPush, $words) {
                        $q->where('full_name', 'LIKE', "%{$cleanPush}%");
                        foreach ($words as $w) {
                            $q->orWhere('full_name', 'LIKE', "%{$w}%");
                        }
                    })->where('status', 'active')->first();
                }
            }

            if ($member) {
                OpenWaService::sendText($fromJid, "🔍 Looking up your active membership profile... Sending your official digital ID card now!");
                OpenWaService::notifyMemberIdCard($member, null, $fromJid);
                return response()->json(['status' => 'card_dispatched']);
            } else {
                OpenWaService::sendText($fromJid, "⚠️ We could not automatically match this chat with an active PMCC-UK membership.\n\n👉 Please reply with your Membership ID or registered mobile:\n*CARD <your ID or phone>*\n_(Example: *CARD PMCC-104* or *CARD 07901296858*)_\n\nOr register online: https://pmccuk.org/membership");
                return response()->json(['status' => 'member_not_found']);
            }
        }

        // 2. TICKET / PASS -> Deliver Upcoming Event Pass
        if (str_starts_with($upperText, 'TICKET') || str_starts_with($upperText, 'PASS') || in_array($upperText, ['TICKETS', 'BOOKING', 'MY TICKET'])) {
            $booking = null;

            // Tier 1: Search by explicit reference
            if ($explicitRef) {
                $booking = EventBooking::where(function ($q) use ($explicitRef) {
                    $q->where('reference', 'LIKE', "%{$explicitRef}%")
                      ->orWhere('id', $explicitRef)
                      ->orWhere('phone', 'LIKE', "%{$explicitRef}%");
                })->where('booking_status', 'approved')->orderBy('id', 'desc')->first();
            }

            // Tier 2: Search by detected phone
            if (!$booking && !empty($phone)) {
                $cleanPn = preg_replace('/[^0-9]/', '', $phone);
                $last10 = substr($cleanPn, -10);
                $booking = EventBooking::where(function ($q) use ($cleanPn, $last10) {
                    $q->where('phone', 'LIKE', "%{$cleanPn}%")
                      ->orWhere('phone', 'LIKE', "%{$last10}%");
                })->where('booking_status', 'approved')->orderBy('id', 'desc')->first();
            }

            // Tier 3: Search by pushName
            if (!$booking && !empty($pushName) && strtolower($pushName) !== 'member') {
                $cleanPush = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $pushName));
                if (strlen($cleanPush) >= 3) {
                    $booking = EventBooking::where('full_name', 'LIKE', "%{$cleanPush}%")
                        ->where('booking_status', 'approved')
                        ->orderBy('id', 'desc')
                        ->first();
                }
            }

            if ($booking) {
                OpenWaService::sendText($fromJid, "🎟️ Fetching your confirmed booking pass... Generating ticket with QR code!");
                OpenWaService::notifyEventTicket($booking, null, $fromJid);
                return response()->json(['status' => 'ticket_dispatched']);
            } else {
                OpenWaService::sendText($fromJid, "⚠️ No approved event bookings were found.\n\n👉 Please reply with your Booking Reference:\n*TICKET <booking ref or phone>*\n_(Example: *TICKET BOOK-1052* or *TICKET 07901296858*)_\n\nOr book tickets here: https://pmccuk.org/events");
                return response()->json(['status' => 'booking_not_found']);
            }
        }

        // 3. EVENTS -> Show Upcoming Events Schedule
        if (in_array($upperText, ['EVENTS', 'EVENT', 'PROGRAMS', 'PROGRAM'])) {
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
                OpenWaService::sendText($fromJid, $reply);
                return response()->json(['status' => 'events_sent']);
            } else {
                OpenWaService::sendText($fromJid, "ℹ️ There are currently no upcoming events scheduled on the portal. Stay tuned to https://pmccuk.org/events for announcements!");
                return response()->json(['status' => 'no_events']);
            }
        }

        // 4. OFFERS / SPONSORS -> Local Plymouth Discounts
        if (in_array($upperText, ['OFFERS', 'OFFER', 'SPONSORS', 'SPONSOR', 'DISCOUNTS'])) {
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
                OpenWaService::sendText($fromJid, $reply);
                return response()->json(['status' => 'offers_sent']);
            } else {
                OpenWaService::sendText($fromJid, "🛍️ Check out all community partner discounts and sponsor offers on our website:\n👉 https://pmccuk.org/offers");
                return response()->json(['status' => 'offers_generic']);
            }
        }

        // 5. STUDENT -> Student Wing Orientation & Support
        if (in_array($upperText, ['STUDENT', 'STUDENTS', 'UNIVERSITY', 'COLLEGE'])) {
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

            OpenWaService::sendText($fromJid, $reply);
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

        OpenWaService::sendText($fromJid, $menu);
        return response()->json(['status' => 'menu_sent']);
    }
}
