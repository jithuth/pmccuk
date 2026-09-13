<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\EventBooking;
use App\Models\Member;
use App\Models\SponsorOffer;
use App\Services\OpenWaService;
use App\Services\TelegramService;
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

        // Clean and sanitize phone number to pure digits
        $phone = null;
        if (!empty($detectedPhone)) {
            $phone = OpenWaService::extractDigits((string) $detectedPhone);
        }
        if (empty($phone) && !str_contains($fromJid, '@lid')) {
            $phone = OpenWaService::extractDigits($fromJid);
        }

        $rawUpper = strtoupper(trim($messageText));
        $upperText = ltrim($rawUpper, '/#!.');
        Log::info("[WhatsApp Inbound] Received '{$upperText}' (raw: '{$messageText}') from {$fromJid} (Phone: {$phone}, PushName: {$pushName})");

        // Check for explicit membership ID or booking reference inside the text (e.g. "CARD PMCC-105", "PMCC-105", "BOOK-102")
        $explicitRef = null;
        if (preg_match('/(PMCC-?\s*\d+|BOOK-?\s*\d+)/i', $messageText, $matches)) {
            $explicitRef = strtoupper(preg_replace('/\s+/', '', trim($matches[0])));
        }

        // 🛡️ 1. INTENT GATE: Only respond if the message is an intentional bot command
        // Normal human messages (e.g. "I will join after 8 pm.", "Hello", "Thanks") must NEVER trigger automated bot replies or rejections!
        if (!$this->isBotCommand($upperText, $explicitRef)) {
            Log::info("[WhatsApp Inbound] Regular conversation from {$fromJid} (Phone: {$phone}): '{$messageText}'. Bypassing bot auto-responder.");
            return response()->json(['status' => 'ignored_regular_chat']);
        }

        // ── 2. Public Information Commands (Available to everyone, no membership required) ──

        // EVENTS -> Upcoming Events Schedule
        if (in_array($upperText, ['EVENTS', 'EVENT', 'PROGRAMS', 'PROGRAM', 'SCHEDULE'])) {
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
                if (OpenWaService::isEventTicketsActive()) {
                    $reply .= "Reply *TICKET* anytime to retrieve your confirmed passes!\n🌐 https://pmccuk.org";
                } else {
                    $reply .= "🌐 Visit https://pmccuk.org/events for registration & event updates!";
                }
                OpenWaService::sendText($fromJid, $reply);
                return response()->json(['status' => 'events_sent']);
            } else {
                OpenWaService::sendText($fromJid, "ℹ️ There are currently no upcoming events scheduled on the portal. Stay tuned to https://pmccuk.org/events for announcements!");
                return response()->json(['status' => 'no_events']);
            }
        }

        // STUDENT -> Student Wing Orientation & Support
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

        // ── 3. Member Verification for Restricted Services ──
        // ZERO-TRUST SECURITY: Member identity is strictly verified from the caller's actual phone number.
        // Explicit reference IDs are NEVER trusted as caller identity to prevent enumeration/harvesting attacks!
        $member = !empty($phone) ? OpenWaService::findMemberByPhone($phone) : null;

        // ── 4. Member Service Execution ──

        // CARD / ID -> Deliver Digital Membership Card
        $isCardRequest = str_starts_with($upperText, 'CARD')
            || str_starts_with($upperText, 'ID')
            || in_array($upperText, ['MY CARD', 'MEMBERSHIP', 'MEMBERSHIP CARD', 'DIGITAL ID'])
            || (!empty($explicitRef) && preg_match('/^PMCC-?\d+$/i', $explicitRef));

        if ($isCardRequest) {
            // Case A: Verified active member
            if ($member) {
                // If caller requested a specific membership reference, verify it strictly belongs to them
                if (!empty($explicitRef) && preg_match('/^PMCC-?\d+$/i', $explicitRef)) {
                    $reqNormalized = strtoupper(str_replace(['-', ' '], '', $explicitRef));
                    $ownNormalized = strtoupper(str_replace(['-', ' '], '', (string)$member->membership_id_assigned));

                    if ($reqNormalized !== $ownNormalized) {
                        Log::warning("[WhatsApp Security] Cross-member card access blocked: Member {$member->full_name} ({$member->membership_id_assigned}, Phone: {$phone}) attempted to fetch card for [{$explicitRef}]");
                        try {
                            ActivityLog::create([
                                'user_type' => 'member',
                                'action' => 'cross_member_card_blocked',
                                'details' => "Security Block: Member {$member->full_name} ({$member->membership_id_assigned}, Phone: {$phone}) attempted unauthorized access to Member ID [{$explicitRef}].",
                                'ip_address' => $request->ip() ?: 'WhatsApp Gateway'
                            ]);
                        } catch (\Throwable $e) {}

                        OpenWaService::sendText($fromJid, "⛔ *Unauthorized Access Blocked*\n\nYou are verified as member *{$member->full_name}* ({$member->membership_id_assigned}). You are only authorized to retrieve your own official membership ID card.\n\nReply *CARD* to receive your membership card.");
                        return response()->json(['status' => 'cross_member_card_blocked']);
                    }
                }

                OpenWaService::sendText($fromJid, "🔍 Verified active membership for *{$member->full_name}* ({$member->membership_id_assigned}). Sending your official digital ID card now!");
                OpenWaService::notifyMemberIdCard($member, null, $fromJid);
                return response()->json(['status' => 'card_dispatched']);
            }

            // Case B: Unverified / unregistered phone number
            // If an unregistered caller specifically attempted to harvest another member's card (e.g. "Card PMCC-105"):
            if (!empty($explicitRef) && preg_match('/^PMCC-?\d+$/i', $explicitRef)) {
                Log::warning("[WhatsApp Security] Unauthorized card harvesting attempt blocked: Caller Phone {$phone} (PushName: {$pushName}, JID: {$fromJid}) targeted [{$explicitRef}]");

                try {
                    ActivityLog::create([
                        'user_type' => 'unauthorized_sender',
                        'action' => 'id_card_harvesting_blocked',
                        'details' => "Security Threat Blocked: Unregistered sender Phone: {$phone} (PushName: {$pushName}, JID: {$fromJid}) attempted to harvest ID Card for [{$explicitRef}]. Dispatched zero-trust block.",
                        'ip_address' => $request->ip() ?: 'WhatsApp Gateway'
                    ]);
                } catch (\Throwable $e) {}

                try {
                    TelegramService::sendMessage(
                        "🚨 <b>PMCC Security Alert: Unauthorized ID Card Harvest Blocked</b>\n\n" .
                        "📱 <b>Attacker/Caller Phone:</b> +{$phone}\n" .
                        "👤 <b>PushName:</b> " . htmlspecialchars($pushName) . "\n" .
                        "🎯 <b>Target Membership Ref:</b> <code>{$explicitRef}</code>\n" .
                        "🛑 <b>Action:</b> Zero-trust defense triggered. Identity details and card PDF withheld."
                    );
                } catch (\Throwable $e) {}

                $securityMsg = "⛔ *Security Verification Failed*\n\n" .
                    "The WhatsApp number you are messaging from is *not registered* as the verified contact for membership *{$explicitRef}*.\n\n" .
                    "🔒 *Data Protection Notice:* PMCC-UK digital ID cards and member credentials are strictly dispatched only to the member's verified WhatsApp number on file.\n\n" .
                    "👉 If you are the registered member and updated your mobile number, please contact PMCC-UK Administration to verify your profile:\n" .
                    "🌐 https://pmccuk.org/contact";

                OpenWaService::sendText($fromJid, $securityMsg);
                return response()->json(['status' => 'harvesting_blocked']);
            }

            // Generic "CARD" request from an unregistered number
            $formattedPhone = $phone ? OpenWaService::formatPhoneDisplay($phone) : 'your WhatsApp account';
            $denialMessage = "⚠️ *PMCC-UK WhatsApp Automated Gateway*\n\n" .
                "Your WhatsApp number ({$formattedPhone}) is not registered with an active PMCC-UK membership.\n\n" .
                "The automated interactive bot and community services (Digital ID Cards, QR Event Passes, Member Discounts) are strictly reserved for registered members.\n\n" .
                "🔒 *Security Notice:* Digital ID Cards are strictly delivered only to the verified mobile number registered in each member's profile.\n\n" .
                "👉 *Not registered yet?* Join our community online:\n" .
                "🌐 https://pmccuk.org/membership";

            OpenWaService::sendText($fromJid, $denialMessage);
            return response()->json(['status' => 'unregistered_rejected']);
        }

        // TICKET / PASS -> Deliver Upcoming Event Pass (Zero-Trust Phone Authentication)
        $isTicketRequest = str_starts_with($upperText, 'TICKET')
            || str_starts_with($upperText, 'PASS')
            || in_array($upperText, ['TICKETS', 'BOOKING', 'MY TICKET', 'EVENT PASS'])
            || (!empty($explicitRef) && preg_match('/^BOOK-?\d+$/i', $explicitRef));

        if ($isTicketRequest) {
            // Guard: Check if automated event ticket delivery is currently active
            if (!OpenWaService::isEventTicketsActive()) {
                $noActiveEventsMsg = "ℹ️ *PMCC-UK Events & Ticketing Notice*\n\n" .
                    "There are currently no active event registrations or ticket issuances open at this time.\n\n" .
                    "Once an active community event is announced, event registrations and pass delivery will be re-enabled.\n\n" .
                    "🌐 Please stay tuned to our official website for upcoming event announcements:\n" .
                    "👉 https://pmccuk.org/events";

                OpenWaService::sendText($fromJid, $noActiveEventsMsg);
                return response()->json(['status' => 'no_active_event_registrations']);
            }

            $booking = null;

            // Tier 1: Explicit booking reference provided -> verify phone or member ownership
            if (!empty($explicitRef) && preg_match('/^BOOK-?\d+$/i', $explicitRef)) {
                $booking = EventBooking::where(function ($q) use ($explicitRef) {
                    $q->where('reference', 'LIKE', "%{$explicitRef}%")
                      ->orWhere('id', $explicitRef);
                })->where('booking_status', 'approved')->orderBy('id', 'desc')->first();

                if ($booking) {
                    // Strict verification: caller phone or verified member profile must match booking
                    $isOwner = false;
                    if (!empty($phone) && OpenWaService::phonesMatch($booking->phone, $phone)) {
                        $isOwner = true;
                    } elseif ($member && !empty($booking->email) && strtolower($booking->email) === strtolower($member->email)) {
                        $isOwner = true;
                    } elseif ($member && OpenWaService::phonesMatch($booking->phone, $member->mobile_number)) {
                        $isOwner = true;
                    }

                    if (!$isOwner) {
                        Log::warning("[WhatsApp Security] Unauthorized ticket retrieval blocked: Caller {$phone} ({$pushName}) targeted Booking Ref [{$explicitRef}]");
                        try {
                            ActivityLog::create([
                                'user_type' => 'unauthorized_sender',
                                'action' => 'ticket_harvesting_blocked',
                                'details' => "Security Threat Blocked: Caller Phone {$phone} ({$pushName}, JID: {$fromJid}) attempted to retrieve Event Ticket [{$explicitRef}]. Caller phone does not match booking contact.",
                                'ip_address' => $request->ip() ?: 'WhatsApp Gateway'
                            ]);
                        } catch (\Throwable $e) {}

                        OpenWaService::sendText($fromJid, "⛔ *Security Verification Failed*\n\nThe WhatsApp number you are messaging from does not match the contact details for booking reference *{$explicitRef}*.\n\n🔒 For data protection, event admission passes can only be delivered to the verified phone number or registered member account used during booking.");
                        return response()->json(['status' => 'ticket_harvesting_blocked']);
                    }

                    OpenWaService::sendText($fromJid, "🎟️ Found your confirmed booking (Ref: {$booking->reference}) for {$booking->full_name}. Generating your QR pass now!");
                    OpenWaService::notifyEventTicket($booking, null, $fromJid);
                    return response()->json(['status' => 'ticket_dispatched']);
                }
            }

            // Tier 2: Search by caller's verified phone number or member profile
            if (!$booking && !empty($phone)) {
                $cleanPn = preg_replace('/[^0-9]/', '', $phone);
                $last10 = substr($cleanPn, -10);

                $booking = EventBooking::where('booking_status', 'approved')
                    ->where(function ($q) use ($cleanPn, $last10, $member) {
                        $q->where('phone', 'LIKE', "%{$cleanPn}%")
                          ->orWhere('phone', 'LIKE', "%{$last10}%");
                        if ($member && !empty($member->email)) {
                            $q->orWhere('email', $member->email);
                        }
                    })->orderBy('id', 'desc')->first();
            }

            if ($booking) {
                OpenWaService::sendText($fromJid, "🎟️ Found your confirmed booking (Ref: {$booking->reference}) for {$booking->full_name}. Generating your QR pass now!");
                OpenWaService::notifyEventTicket($booking, null, $fromJid);
                return response()->json(['status' => 'ticket_dispatched']);
            } else {
                $mName = $member ? " ({$member->full_name})" : "";
                $formattedPhone = $phone ? OpenWaService::formatPhoneDisplay($phone) : 'your phone number';
                OpenWaService::sendText($fromJid, "⚠️ No approved event bookings were found for {$formattedPhone}{$mName}.\n\n👉 To book event tickets, please visit:\n🌐 https://pmccuk.org/events");
                return response()->json(['status' => 'booking_not_found']);
            }
        }

        // OFFERS / SPONSORS -> Local Plymouth Discounts
        if (in_array($upperText, ['OFFERS', 'OFFER', 'SPONSORS', 'SPONSOR', 'DISCOUNTS', 'DISCOUNT'])) {
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

        // ── 5. HELP / MENU Commands (Explicitly requested) ──
        if (in_array($upperText, ['HELP', 'MENU', 'START', 'BOT', 'COMMANDS', 'INFO'])) {
            $isTicketActive = OpenWaService::isEventTicketsActive();
            if ($member) {
                $displayName = $member->full_name ?: $pushName;
                $ticketOption = $isTicketActive
                    ? "👉 *TICKET* - Retrieve your Event Admission Ticket (QR Pass)\n"
                    : "👉 *TICKET* - Event Admission Pass _(Currently closed - no active events)_\n";
                $menu = "🌟 *Welcome to PMCC-UK Interactive WhatsApp!* 🇬🇧\n\n" .
                    "Hello *{$displayName}* (Member: *{$member->membership_id_assigned}*), reply with any keyword below for instant assistance:\n\n" .
                    "👉 *CARD* - Download your Digital Membership Card (PDF)\n" .
                    $ticketOption .
                    "👉 *EVENTS* - View upcoming community festivals & bookings\n" .
                    "👉 *OFFERS* - Explore member discounts at Plymouth restaurants & shops\n" .
                    "👉 *STUDENT* - Student Wing orientation & university support\n" .
                    "👉 *HELP* - View this quick menu\n\n" .
                    "🌐 Official Website: https://pmccuk.org";
            } else {
                $ticketOption = $isTicketActive
                    ? "👉 *TICKET* - Retrieve Event Admission Ticket (Approved bookings)\n"
                    : "👉 *TICKET* - Event Admission Pass _(Currently closed - no active events)_\n";
                $menu = "🌟 *Welcome to PMCC-UK WhatsApp Assistant!* 🇬🇧\n\n" .
                    "Reply with any keyword below:\n\n" .
                    "👉 *EVENTS* - View upcoming community festivals & bookings\n" .
                    "👉 *STUDENT* - Student Wing orientation & university support\n" .
                    "👉 *CARD* - Retrieve Digital Membership Card (Registered members)\n" .
                    $ticketOption . "\n" .
                    "👉 *Join PMCC-UK Online:*\n" .
                    "🌐 https://pmccuk.org/membership\n\n" .
                    "🌐 Official Website: https://pmccuk.org";
            }

            OpenWaService::sendText($fromJid, $menu);
            return response()->json(['status' => 'menu_sent']);
        }

        // Any other message that passed is ignored to prevent spamming
        return response()->json(['status' => 'ignored']);
    }

    /**
     * Determine if an inbound message text is an intentional PMCC bot command.
     */
    protected function isBotCommand(string $upperText, ?string $explicitRef): bool
    {
        // Explicit membership ID or booking reference
        if (!empty($explicitRef) && preg_match('/^(PMCC-?\d+|BOOK-?\d+)$/i', $explicitRef)) {
            return true;
        }

        $exactCommands = [
            'HELP', 'MENU', 'START', 'BOT', 'COMMANDS', 'INFO',
            'CARD', 'ID', 'MY CARD', 'MEMBERSHIP', 'MEMBERSHIP CARD', 'DIGITAL ID',
            'TICKET', 'PASS', 'TICKETS', 'BOOKING', 'MY TICKET', 'EVENT PASS', 'PASSES',
            'EVENTS', 'EVENT', 'PROGRAMS', 'PROGRAM', 'SCHEDULE',
            'OFFERS', 'OFFER', 'SPONSORS', 'SPONSOR', 'DISCOUNTS', 'DISCOUNT',
            'STUDENT', 'STUDENTS', 'UNIVERSITY', 'COLLEGE'
        ];

        if (in_array($upperText, $exactCommands, true)) {
            return true;
        }

        $prefixCommands = ['CARD ', 'ID ', 'TICKET ', 'PASS ', 'BOOKING '];
        foreach ($prefixCommands as $prefix) {
            if (str_starts_with($upperText, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
