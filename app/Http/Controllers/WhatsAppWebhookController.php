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
        $messageText = trim((string) ($request->input('message') ?: $request->input('body', '')));
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

        // 🛡️ MEMBER REGISTRATION & IDENTITY VERIFICATION GATE:
        // "fetch the details exactly same from the members data and if the number is without a country code add +44 to them.
        // If the number is not registered, the whatsapp interactive system should not work."
        $lookupTarget = $explicitRef ?: $phone;
        $member = OpenWaService::findMemberByPhone($lookupTarget);

        if (!$member) {
            Log::warning("[WhatsApp Bot] Access denied for unregistered sender: {$fromJid} (Phone: {$phone}, PushName: {$pushName})");

            $cleanDisplay = $phone ? ('+' . OpenWaService::formatPhone($phone)) : 'your number';
            $denialMessage = "⚠️ *PMCC-UK WhatsApp Automated Gateway*\n\n" .
                "Your WhatsApp number ({$cleanDisplay}) is not registered with an active PMCC-UK membership.\n\n" .
                "The automated interactive bot and community services (Digital ID Cards, QR Event Passes, Member Discounts) are strictly reserved for registered members.\n\n" .
                "👉 *Already a member?* Reply with your registered Membership ID:\n" .
                "*(Example: CARD PMCC-104)*\n\n" .
                "👉 *Not registered yet?* Join our community online:\n" .
                "🌐 https://pmccuk.org/membership";

            OpenWaService::sendText($fromJid, $denialMessage);
            return response()->json(['status' => 'unregistered_rejected']);
        }

        // ── Command Routing for Registered Member ──

        // 1. CARD / ID -> Deliver Digital Membership Card
        if (str_starts_with($upperText, 'CARD') || str_starts_with($upperText, 'ID') || in_array($upperText, ['MY CARD', 'MEMBERSHIP', 'MEMBERSHIP CARD'])) {
            OpenWaService::sendText($fromJid, "🔍 Verified active membership for *{$member->full_name}* ({$member->membership_id_assigned}). Sending your official digital ID card now!");
            OpenWaService::notifyMemberIdCard($member, null, $fromJid);
            return response()->json(['status' => 'card_dispatched']);
        }

        // 2. TICKET / PASS -> Deliver Upcoming Event Pass
        if (str_starts_with($upperText, 'TICKET') || str_starts_with($upperText, 'PASS') || in_array($upperText, ['TICKETS', 'BOOKING', 'MY TICKET'])) {
            $booking = null;

            // Tier 1: Search by explicit booking reference
            if ($explicitRef) {
                $booking = EventBooking::where(function ($q) use ($explicitRef) {
                    $q->where('reference', 'LIKE', "%{$explicitRef}%")
                      ->orWhere('id', $explicitRef)
                      ->orWhere('phone', 'LIKE', "%{$explicitRef}%");
                })->where('booking_status', 'approved')->orderBy('id', 'desc')->first();
            }

            // Tier 2: Search by verified member's phone number or email
            if (!$booking && $member) {
                $mPhone = OpenWaService::formatPhone($member->mobile_number);
                $cleanPn = preg_replace('/[^0-9]/', '', $mPhone ?: $phone);
                $last10 = substr($cleanPn, -10);

                $booking = EventBooking::where(function ($q) use ($cleanPn, $last10, $member) {
                    $q->where('phone', 'LIKE', "%{$cleanPn}%")
                      ->orWhere('phone', 'LIKE', "%{$last10}%")
                      ->orWhere('email', $member->email)
                      ->orWhere('full_name', 'LIKE', "%{$member->full_name}%");
                })->where('booking_status', 'approved')->orderBy('id', 'desc')->first();
            }

            if ($booking) {
                OpenWaService::sendText($fromJid, "🎟️ Found your confirmed booking (Ref: {$booking->reference}) for {$booking->full_name}. Generating your QR pass now!");
                OpenWaService::notifyEventTicket($booking, null, $fromJid);
                return response()->json(['status' => 'ticket_dispatched']);
            } else {
                OpenWaService::sendText($fromJid, "⚠️ No approved event bookings were found for your membership profile ({$member->full_name}).\n\n👉 If you booked under a different reference or phone, please reply:\n*TICKET <booking ref or phone>*\n_(Example: *TICKET BOOK-1052* or *TICKET 07901296858*)_\n\nOr book tickets online: https://pmccuk.org/events");
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
        $displayName = $member->full_name ?: $pushName;
        $menu = "🌟 *Welcome to PMCC-UK Interactive WhatsApp!* 🇬🇧\n\n" .
            "Hello *{$displayName}* (Member: *{$member->membership_id_assigned}*), reply with any keyword below for instant assistance:\n\n" .
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
