<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TelegramService;
use App\Models\Member;
use App\Models\RenewalRequest;
use App\Models\EventBooking;
use App\Models\FinancialTransaction;
use App\Models\ActivityLog;
use App\Mail\MemberIdCardEmail;
use App\Mail\EventTicketMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class TelegramWebhookController extends Controller
{
    /**
     * Handle incoming Webhook updates from Telegram API.
     */
    public function handle(Request $request)
    {
        $update = $request->all();

        // 1. Handle Inline Keyboard Button Click (Callback Query)
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return response()->json(['status' => 'ok']);
        }

        // 2. Handle Text Message Commands and State Inputs
        if (isset($update['message']['text'])) {
            $this->handleTextMessage($update['message']);
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['status' => 'ignored']);
    }

    /**
     * Handle Telegram Callback Queries (Inline Button Clicks)
     */
    protected function handleCallbackQuery(array $callbackQuery)
    {
        $callbackId = $callbackQuery['id'];
        $chatId = (string) $callbackQuery['message']['chat']['id'];
        $messageId = (int) $callbackQuery['message']['message_id'];
        $data = $callbackQuery['data'] ?? '';
        $fromId = $callbackQuery['from']['id'] ?? $chatId;
        $adminName = $callbackQuery['from']['first_name'] ?? 'Admin';

        if (empty($data)) {
            TelegramService::answerCallbackQuery($callbackId, "No action payload.");
            return;
        }

        // Menu Actions (Interactive User Services)
        if (str_starts_with($data, 'menu_action:')) {
            $actionType = str_replace('menu_action:', '', $data);
            $this->handleMenuActionClick($actionType, $fromId, $chatId, $callbackId);
            return;
        }

        // Admin Approval Actions
        $parts = explode(':', $data);
        $action = $parts[0] ?? '';
        $id = $parts[1] ?? null;

        if (!$id) {
            TelegramService::answerCallbackQuery($callbackId, "Invalid action ID.");
            return;
        }

        switch ($action) {
            case 'approve_mem':
                $this->processMemberApproval($id, $callbackId, $chatId, $messageId, $adminName);
                break;

            case 'decline_mem':
                $this->processMemberDecline($id, $callbackId, $chatId, $messageId, $adminName);
                break;

            case 'approve_ren':
                $this->processRenewalApproval($id, $callbackId, $chatId, $messageId, $adminName);
                break;

            case 'decline_ren':
                $this->processRenewalDecline($id, $callbackId, $chatId, $messageId, $adminName);
                break;

            case 'approve_book':
                $this->promptBookingApprovalConfirmation($id, $callbackId, $chatId, $messageId, $adminName);
                break;

            case 'confirm_approve_book':
                $this->processBookingApproval($id, $callbackId, $chatId, $messageId, $adminName);
                break;

            case 'cancel_book':
                $this->processBookingCancelPrompt($id, $callbackId, $chatId, $messageId, $adminName);
                break;

            case 'decline_book':
                $this->processBookingDecline($id, $callbackId, $chatId, $messageId, $adminName);
                break;

            default:
                TelegramService::answerCallbackQuery($callbackId, "Unknown action: {$action}");
                break;
        }
    }

    /**
     * Handle Menu Action Click (ID Card Download, Ticket Download, Status Check, Expiry Check)
     */
    protected function handleMenuActionClick(string $actionType, $fromId, string $chatId, string $callbackId)
    {
        switch ($actionType) {
            case 'pending_queue':
                TelegramService::answerCallbackQuery($callbackId, "Fetching pending approvals...");
                $pendingMembers = Member::where('status', 'pending')->count();
                $pendingRenewals = RenewalRequest::where('status', 'pending')->count();
                $pendingBookings = EventBooking::where('booking_status', 'pending')->count();

                $msg = "⏳ <b>PMCC-UK Pending Approvals Hub</b>\n\n" .
                    "👤 <b>Pending Memberships:</b> <code>{$pendingMembers}</code>\n" .
                    "🔄 <b>Pending Renewals:</b> <code>{$pendingRenewals}</code>\n" .
                    "🎟️ <b>Pending Ticket Bookings:</b> <code>{$pendingBookings}</code>\n\n";

                if ($pendingMembers == 0 && $pendingRenewals == 0 && $pendingBookings == 0) {
                    $msg .= "✨ <i>All approval queues are completely clear!</i>";
                } else {
                    $msg .= "⚡ <i>Use direct commands like <code>/approve_mem [ID]</code> or click Telegram alert buttons to approve items instantly.</i>";
                }

                TelegramService::sendMessageToChat($chatId, $msg);
                break;

            case 'admin_stats':
                TelegramService::answerCallbackQuery($callbackId, "Calculating live stats...");
                $activeMembers = Member::where('status', 'active')->count();
                $pendingMembers = Member::where('status', 'pending')->count();
                $approvedBookings = EventBooking::where('booking_status', 'approved')->count();
                $totalRevenue = EventBooking::where('booking_status', 'approved')->sum('total_amount');
                $monthIncome = FinancialTransaction::where('type', 'income')
                    ->whereYear('transaction_date', date('Y'))
                    ->whereMonth('transaction_date', date('m'))
                    ->sum('amount');

                $msg = "📊 <b>PMCC-UK Real-Time Dashboard Stats</b>\n\n" .
                    "👥 <b>Active Members:</b> <code>{$activeMembers}</code>\n" .
                    "⏳ <b>Pending Memberships:</b> <code>{$pendingMembers}</code>\n" .
                    "🎟️ <b>Approved Ticket Bookings:</b> <code>{$approvedBookings}</code>\n" .
                    "💰 <b>Total Ticket Sales:</b> <code>£" . number_format($totalRevenue, 2) . "</code>\n" .
                    "💳 <b>This Month's Ledger Income:</b> <code>£" . number_format($monthIncome, 2) . "</code>";

                TelegramService::sendMessageToChat($chatId, $msg);
                break;

            case 'download_pdf':
                Cache::put("tg_user_state_{$fromId}", 'download_pdf', 600);
                TelegramService::answerCallbackQuery($callbackId, "Please enter Membership ID");
                TelegramService::sendMessageToChat(
                    $chatId,
                    "🪪 <b>Download Membership ID Card (PDF)</b>\n\n" .
                    "Please reply with the <b>Membership ID</b> (e.g. <code>PMCC-1052</code> or <code>386</code>):"
                );
                break;

            case 'download_ticket':
                Cache::put("tg_user_state_{$fromId}", 'download_ticket', 600);
                TelegramService::answerCallbackQuery($callbackId, "Please enter Ticket Reference");
                TelegramService::sendMessageToChat(
                    $chatId,
                    "🎟️ <b>Download Event Entry Ticket (PDF)</b>\n\n" .
                    "Please reply with the <b>Membership ID</b> (e.g. <code>PMCC-1052</code> or <code>386</code>) or <b>Ticket Reference Number</b> (e.g. <code>BOOK-42</code>):"
                );
                break;

            case 'lookup':
                Cache::put("tg_user_state_{$fromId}", 'lookup', 600);
                TelegramService::answerCallbackQuery($callbackId, "Enter Member ID or Ticket Ref");
                TelegramService::sendMessageToChat(
                    $chatId,
                    "🔍 <b>Member / Ticket Quick Lookup</b>\n\n" .
                    "Please reply with any <b>Membership ID</b> (e.g. <code>PMCC-1052</code> or <code>386</code>) or <b>Ticket Reference</b> (e.g. <code>BOOK-42</code>):"
                );
                break;

            case 'system_logs':
                TelegramService::answerCallbackQuery($callbackId, "Fetching system logs...");
                $logs = \App\Models\ActivityLog::latest()->take(5)->get();
                $msg = "🚨 <b>PMCC-UK Recent Activity Logs</b>\n\n";

                if ($logs->isEmpty()) {
                    $msg .= "<i>No recent activity logs recorded.</i>";
                } else {
                    foreach ($logs as $l) {
                        $dateStr = \Carbon\Carbon::parse($l->created_at)->format('d M H:i');
                        $msg .= "• <b>{$dateStr}</b>: " . htmlspecialchars($l->action) . "\n" .
                            "  <i>" . htmlspecialchars(substr($l->details, 0, 90)) . "</i>\n\n";
                    }
                }

                TelegramService::sendMessageToChat($chatId, $msg);
                break;

            case 'support':
                TelegramService::answerCallbackQuery($callbackId, "Admin Support & Help");
                TelegramService::sendMessageToChat(
                    $chatId,
                    "❓ <b>PMCC-UK Admin Support & Help</b>\n\n" .
                    "⚡ <b>Available Admin Commands:</b>\n" .
                    "• Send <b>/menu</b> - Open Admin Control Panel\n" .
                    "• <code>/approve_mem [ID]</code> - Approve member ID directly\n" .
                    "• <code>/decline_mem [ID]</code> - Decline member ID directly\n\n" .
                    "🌐 <b>Website:</b> https://pmccuk.org\n" .
                    "📧 <b>Email:</b> info@pmccuk.org\n" .
                    "📍 <b>Location:</b> Plymouth, United Kingdom"
                );
                break;

            case 'export_members':
                TelegramService::answerCallbackQuery($callbackId, "Generating Members CSV...");
                try {
                    $members = Member::where('status', 'active')->orderBy('id', 'asc')->get();
                    $csvFileName = "PMCC_Active_Members_" . date('Y_m_d') . ".csv";
                    $tempPath = storage_path("app/{$csvFileName}");

                    $fp = fopen($tempPath, 'w');
                    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
                    fputcsv($fp, ['ID', 'Reg No', 'Full Name', 'Email', 'Phone', 'Expiry Date', 'Status']);

                    foreach ($members as $m) {
                        $reg = $m->membership_id_assigned ?: ($m->prev_membership_no ?: "PMCC-{$m->id}");
                        fputcsv($fp, [
                            $m->id,
                            $reg,
                            $m->full_name,
                            $m->email ?? 'N/A',
                            $m->phone ?? 'N/A',
                            $m->expiry_date ?? 'N/A',
                            strtoupper($m->status)
                        ]);
                    }
                    fclose($fp);

                    TelegramService::sendDocument(
                        $chatId,
                        $tempPath,
                        $csvFileName,
                        "📄 <b>PMCC-UK Active Members Roster Export</b>\n\nTotal Active Members: <code>" . count($members) . "</code>"
                    );

                    if (file_exists($tempPath)) unlink($tempPath);
                } catch (\Exception $e) {
                    Log::error("Telegram Export Members Failed: " . $e->getMessage());
                    TelegramService::sendMessageToChat($chatId, "❌ <b>Export Error:</b> " . htmlspecialchars($e->getMessage()));
                }
                break;

            case 'check_in_summary':
                TelegramService::answerCallbackQuery($callbackId, "Fetching event check-ins...");
                $activeEvent = \App\Models\Event::orderBy('event_date', 'desc')->first();
                if (!$activeEvent) {
                    TelegramService::sendMessageToChat($chatId, "🎟️ <b>No active events found.</b>");
                    break;
                }

                $approvedBookings = EventBooking::where('event_id', $activeEvent->id)
                    ->where('booking_status', 'approved')
                    ->get();

                $totalApproved = $approvedBookings->count();
                $totalHeads = $approvedBookings->sum(fn($b) => $b->adult_count + $b->child_count + $b->infant_count);
                $checkedInCount = $approvedBookings->whereNotNull('check_in_at')->count();
                $checkedInHeads = $approvedBookings->whereNotNull('check_in_at')->sum(fn($b) => $b->adult_count + $b->child_count + $b->infant_count);
                $remainingCount = $totalApproved - $checkedInCount;

                $rate = $totalApproved > 0 ? round(($checkedInCount / $totalApproved) * 100, 1) : 0;

                $msg = "🎟️ <b>Live Event Check-In Summary</b>\n\n" .
                    "📅 <b>Event:</b> " . htmlspecialchars($activeEvent->title) . "\n" .
                    "📆 <b>Date:</b> " . htmlspecialchars($activeEvent->event_date) . "\n\n" .
                    "🎫 <b>Approved Bookings:</b> <code>{$totalApproved}</code> ({$totalHeads} Total Heads)\n" .
                    "✅ <b>Checked In (Scanned):</b> <code>{$checkedInCount}</code> ({$checkedInHeads} Heads)\n" .
                    "⏳ <b>Pending Entry:</b> <code>{$remainingCount}</code>\n" .
                    "📊 <b>Attendance Rate:</b> <code>{$rate}%</code>";

                TelegramService::sendMessageToChat($chatId, $msg);
                break;

            case 'broadcast':
                Cache::put("tg_user_state_{$fromId}", 'broadcast', 600);
                TelegramService::answerCallbackQuery($callbackId, "Enter broadcast message");
                TelegramService::sendMessageToChat(
                    $chatId,
                    "📢 <b>Send Telegram Broadcast Announcement</b>\n\n" .
                    "Please reply with the <b>text message</b> you wish to broadcast to the PMCC Telegram Channel."
                );
                break;

            case 'maint_status':
                TelegramService::answerCallbackQuery($callbackId, "Checking website status...");
                $isDown = app()->isDownForMaintenance();
                $statusMsg = $isDown ? "🔴 <b>MAINTENANCE MODE ACTIVE</b>" : "🟢 <b>WEBSITE ONLINE & ACTIVE</b>";
                
                $msg = "🌐 <b>PMCC-UK Website Maintenance Status</b>\n\n" .
                    "Status: {$statusMsg}\n" .
                    "URL: https://pmccuk.org\n\n" .
                    "<i>Server & Database connections operational.</i>";

                TelegramService::sendMessageToChat($chatId, $msg);
                break;

            default:
                TelegramService::answerCallbackQuery($callbackId, "Unknown menu action.");
                break;
        }
    }

    /**
     * Process Member Approval via Telegram
     */
    protected function processMemberApproval($id, string $callbackId, string $chatId, int $messageId, string $adminName)
    {
        $member = Member::find($id);
        if (!$member) {
            TelegramService::answerCallbackQuery($callbackId, "Member #{$id} not found.", true);
            return;
        }

        if ($member->status === 'active') {
            TelegramService::answerCallbackQuery($callbackId, "Member {$member->full_name} is already ACTIVE!", true);
            TelegramService::editMessageText($chatId, $messageId, "✅ <b>Member Already Approved</b>\n\n👤 <b>Name:</b> " . htmlspecialchars($member->full_name) . "\n🆔 <b>Reg No:</b> <code>" . ($member->membership_id_assigned ?? 'N/A') . "</code>");
            return;
        }

        $assignedNo = $member->membership_id_assigned;
        if (empty($assignedNo)) {
            $nextVal = 1000 + $member->id;
            $assignedNo = "PMCC-{$nextVal}";
        }

        $expiryDate = Carbon::now()->addYear()->subDay()->format('Y-m-d');
        $paymentDate = Carbon::now()->format('Y-m-d');

        $updateData = [
            'status' => 'active',
            'membership_id_assigned' => $assignedNo,
            'expiry_date' => $expiryDate,
            'payment_date' => $paymentDate,
            'payment_amount' => $member->payment_amount ?: '5.00',
        ];

        if (empty($member->guid)) {
            $updateData['guid'] = (string) \Illuminate\Support\Str::uuid();
        }

        $member->update($updateData);

        try {
            FinancialTransaction::create([
                'type' => 'income',
                'category' => 'Membership Fee',
                'amount' => $member->payment_amount ?: 5.00,
                'transaction_date' => $paymentDate,
                'description' => "Membership Fee for {$member->full_name} (Approved via Telegram by {$adminName})",
                'ref_no' => "TG-" . time(),
                'payment_method' => 'Telegram Quick Approve'
            ]);
        } catch (\Exception $e) {}

        try {
            ActivityLog::create([
                'user_type' => 'telegram_admin',
                'action' => 'approve_member_telegram',
                'details' => "Member {$member->full_name} (ID: {$id}) approved via Telegram by {$adminName}. Reg No: {$assignedNo}",
                'ip_address' => 'Telegram Bot API'
            ]);
        } catch (\Exception $e) {}

        if (!empty($member->email)) {
            try {
                Mail::to($member->email)->send(new MemberIdCardEmail($member));
            } catch (\Exception $e) {
                Log::error("ID Card email failed during Telegram approval: " . $e->getMessage());
            }
        }

        TelegramService::answerCallbackQuery($callbackId, "✅ Approved {$member->full_name} ({$assignedNo})!", true);

        $newText = "✅ <b>MEMBER APPROVED VIA TELEGRAM</b>\n\n" .
            "👤 <b>Name:</b> " . htmlspecialchars($member->full_name) . "\n" .
            "🆔 <b>Assigned Reg No:</b> <code>{$assignedNo}</code>\n" .
            "📅 <b>Expiry Date:</b> {$expiryDate}\n" .
            "⚡ <b>Approved By:</b> {$adminName}\n" .
            "📧 <i>Digital ID card dispatched automatically.</i>";

        TelegramService::editMessageText($chatId, $messageId, $newText);
    }

    /**
     * Process Member Decline via Telegram
     */
    protected function processMemberDecline($id, string $callbackId, string $chatId, int $messageId, string $adminName)
    {
        $member = Member::find($id);
        if (!$member) {
            TelegramService::answerCallbackQuery($callbackId, "Member #{$id} not found.", true);
            return;
        }

        $member->update(['status' => 'rejected']);

        try {
            ActivityLog::create([
                'user_type' => 'telegram_admin',
                'action' => 'decline_member_telegram',
                'details' => "Member {$member->full_name} (ID: {$id}) declined via Telegram by {$adminName}",
                'ip_address' => 'Telegram Bot API'
            ]);
        } catch (\Exception $e) {}

        TelegramService::answerCallbackQuery($callbackId, "❌ Declined membership for {$member->full_name}", true);

        $newText = "❌ <b>MEMBER REGISTRATION DECLINED</b>\n\n" .
            "👤 <b>Name:</b> " . htmlspecialchars($member->full_name) . "\n" .
            "📧 <b>Email:</b> " . htmlspecialchars($member->email ?? 'N/A') . "\n" .
            "⚡ <b>Declined By:</b> {$adminName}";

        TelegramService::editMessageText($chatId, $messageId, $newText);
    }

    /**
     * Process Renewal Approval via Telegram
     */
    protected function processRenewalApproval($id, string $callbackId, string $chatId, int $messageId, string $adminName)
    {
        $renewal = RenewalRequest::find($id);
        if (!$renewal) {
            TelegramService::answerCallbackQuery($callbackId, "Renewal Request #{$id} not found.", true);
            return;
        }

        if ($renewal->status === 'approved') {
            TelegramService::answerCallbackQuery($callbackId, "Renewal request already approved!", true);
            return;
        }

        $renewal->update(['status' => 'approved', 'processed_at' => now()]);

        $member = Member::where('membership_id_assigned', $renewal->membership_no)->first();
        if ($member) {
            $newExpiry = Carbon::now()->addYear()->subDay()->format('Y-m-d');
            $member->update(['expiry_date' => $newExpiry, 'status' => 'active']);

            if (!empty($member->email)) {
                try {
                    Mail::to($member->email)->send(new MemberIdCardEmail($member));
                } catch (\Exception $e) {}
            }
        }

        TelegramService::answerCallbackQuery($callbackId, "✅ Renewal approved for {$renewal->full_name}!", true);

        $newText = "✅ <b>RENEWAL APPROVED VIA TELEGRAM</b>\n\n" .
            "👤 <b>Name:</b> " . htmlspecialchars($renewal->full_name) . "\n" .
            "🆔 <b>Membership No:</b> <code>{$renewal->membership_no}</code>\n" .
            "⚡ <b>Approved By:</b> {$adminName}";

        TelegramService::editMessageText($chatId, $messageId, $newText);
    }

    /**
     * Process Renewal Decline via Telegram
     */
    protected function processRenewalDecline($id, string $callbackId, string $chatId, int $messageId, string $adminName)
    {
        $renewal = RenewalRequest::find($id);
        if (!$renewal) {
            TelegramService::answerCallbackQuery($callbackId, "Renewal Request #{$id} not found.", true);
            return;
        }

        $renewal->update(['status' => 'rejected']);

        TelegramService::answerCallbackQuery($callbackId, "❌ Renewal request declined for {$renewal->full_name}", true);

        $newText = "❌ <b>RENEWAL REQUEST DECLINED</b>\n\n" .
            "👤 <b>Name:</b> " . htmlspecialchars($renewal->full_name) . "\n" .
            "🆔 <b>Membership No:</b> <code>{$renewal->membership_no}</code>\n" .
            "⚡ <b>Declined By:</b> {$adminName}";

        TelegramService::editMessageText($chatId, $messageId, $newText);
    }

    /**
     * Prompt Confirmation for Booking Approval via Telegram
     */
    protected function promptBookingApprovalConfirmation($id, string $callbackId, string $chatId, int $messageId, string $adminName)
    {
        $booking = EventBooking::find($id);
        if (!$booking) {
            TelegramService::answerCallbackQuery($callbackId, "Booking #{$id} not found.", true);
            return;
        }

        // If already approved, return message already approved
        if ($booking->booking_status === 'approved') {
            TelegramService::answerCallbackQuery($callbackId, "Booking #{$id} is already APPROVED!", true);
            $alreadyText = "✅ <b>BOOKING ALREADY APPROVED</b>\n\n" .
                "🎟️ <b>Ticket Ref:</b> <code>" . ($booking->reference_no ?? "BOOK-{$booking->id}") . "</code>\n" .
                "👤 <b>Attendee:</b> " . htmlspecialchars($booking->full_name) . "\n" .
                "📧 <b>Email:</b> " . htmlspecialchars($booking->email) . "\n" .
                "⚡ <b>Status:</b> Approved";
            TelegramService::editMessageText($chatId, $messageId, $alreadyText);
            return;
        }

        TelegramService::answerCallbackQuery($callbackId, "Confirmation required.");

        $totalTickets = $booking->adult_count + $booking->child_count + $booking->infant_count;
        $confirmText = "⚠️ <b>CONFIRM BOOKING APPROVAL</b>\n\n" .
            "Are you sure you want to approve this event ticket booking?\n\n" .
            "📅 <b>Event:</b> " . htmlspecialchars($booking->event->title ?? 'PMCC Event') . "\n" .
            "👤 <b>Booked By:</b> " . htmlspecialchars($booking->full_name) . "\n" .
            "📧 <b>Email:</b> " . htmlspecialchars($booking->email) . "\n" .
            "📱 <b>Phone:</b> " . htmlspecialchars($booking->phone) . "\n" .
            "🎫 <b>Tickets:</b> {$totalTickets} ({$booking->adult_count} Adult, {$booking->child_count} Child, {$booking->infant_count} Infant)\n" .
            "💰 <b>Total Amount:</b> £" . number_format($booking->total_amount, 2);

        $keyboard = [
            [
                ['text' => '✅ Confirm Approval', 'callback_data' => "confirm_approve_book:{$booking->id}"],
                ['text' => '❌ Cancel', 'callback_data' => "cancel_book:{$booking->id}"]
            ]
        ];

        TelegramService::editMessageText($chatId, $messageId, $confirmText, $keyboard);
    }

    /**
     * Process Booking Approval via Telegram
     */
    protected function processBookingApproval($id, string $callbackId, string $chatId, int $messageId, string $adminName)
    {
        $booking = EventBooking::find($id);
        if (!$booking) {
            TelegramService::answerCallbackQuery($callbackId, "Booking #{$id} not found.", true);
            return;
        }

        // If already approved, return message already approved
        if ($booking->booking_status === 'approved') {
            TelegramService::answerCallbackQuery($callbackId, "Booking #{$id} is already APPROVED!", true);
            $alreadyText = "✅ <b>BOOKING ALREADY APPROVED</b>\n\n" .
                "🎟️ <b>Ticket Ref:</b> <code>" . ($booking->reference_no ?? "BOOK-{$booking->id}") . "</code>\n" .
                "👤 <b>Attendee:</b> " . htmlspecialchars($booking->full_name) . "\n" .
                "📧 <b>Email:</b> " . htmlspecialchars($booking->email) . "\n" .
                "⚡ <b>Status:</b> Approved";
            TelegramService::editMessageText($chatId, $messageId, $alreadyText);
            return;
        }

        $booking->update([
            'booking_status' => 'approved',
            'payment_status' => 'paid',
            'check_in_status' => 'pending'
        ]);

        if ($booking->total_amount > 0) {
            try {
                FinancialTransaction::create([
                    'type' => 'income',
                    'category' => 'Event Ticket',
                    'amount' => $booking->total_amount,
                    'transaction_date' => now()->format('Y-m-d'),
                    'description' => "Event Booking: {$booking->full_name} for " . ($booking->event->title ?? 'Event') . " (Ref: #{$booking->id})",
                    'payment_method' => 'Telegram Quick Approve',
                    'ref_no' => "EVT-{$booking->id}"
                ]);
            } catch (\Exception $e) {
                Log::error("FinancialTransaction creation failed during Telegram booking approval: " . $e->getMessage());
            }
        }

        // Email ticket to attendee
        if (!empty($booking->email)) {
            try {
                Mail::to($booking->email)->send(new EventTicketMail($booking));
            } catch (\Exception $e) {
                Log::error("EventTicketMail failed during Telegram booking approval: " . $e->getMessage());
            }
        }

        // Generate and dispatch Ticket PDF via Telegram
        try {
            $refNo = $booking->reference_no ?: ("BOOK-" . ($booking->membership_no != 'NON-MEMBER' ? $booking->membership_no : 'NM') . "-" . $booking->id);
            $pdf = Pdf::loadView('events.pdf_ticket', compact('booking'))
                ->setPaper([0, 0, 396, 252], 'landscape')
                ->setOption('isRemoteEnabled', true);

            $tempPath = storage_path("app/PMCC_Ticket_{$booking->id}.pdf");
            $pdf->save($tempPath);

            TelegramService::sendDocument(
                $chatId,
                $tempPath,
                "PMCC_Event_Ticket_{$refNo}.pdf",
                "🎟️ <b>Official PMCC-UK Event Ticket PDF</b>\n\n" .
                "📅 <b>Event:</b> " . htmlspecialchars($booking->event->title ?? 'PMCC Event') . "\n" .
                "👤 <b>Attendee:</b> " . htmlspecialchars($booking->full_name) . "\n" .
                "🎟️ <b>Ticket Ref:</b> <code>{$refNo}</code>"
            );

            if (file_exists($tempPath)) unlink($tempPath);
        } catch (\Exception $e) {}

        TelegramService::answerCallbackQuery($callbackId, "✅ Event booking confirmed!", true);

        $newText = "✅ <b>BOOKING CONFIRMED VIA TELEGRAM</b>\n\n" .
            "🎟️ <b>Ticket Ref:</b> <code>" . ($booking->reference_no ?? "BOOK-{$booking->id}") . "</code>\n" .
            "👤 <b>Attendee:</b> " . htmlspecialchars($booking->full_name) . "\n" .
            "⚡ <b>Confirmed By:</b> {$adminName}\n" .
            "📧 <i>Ticket PDF emailed and attached.</i>";

        TelegramService::editMessageText($chatId, $messageId, $newText);
    }

    /**
     * Cancel confirmation prompt for Booking Approval
     */
    protected function processBookingCancelPrompt($id, string $callbackId, string $chatId, int $messageId, string $adminName)
    {
        $booking = EventBooking::find($id);
        if (!$booking) {
            TelegramService::answerCallbackQuery($callbackId, "Booking #{$id} not found.", true);
            return;
        }

        if ($booking->booking_status === 'approved') {
            TelegramService::answerCallbackQuery($callbackId, "Booking #{$id} is already APPROVED!", true);
            $alreadyText = "✅ <b>BOOKING ALREADY APPROVED</b>\n\n" .
                "🎟️ <b>Ticket Ref:</b> <code>" . ($booking->reference_no ?? "BOOK-{$booking->id}") . "</code>\n" .
                "👤 <b>Attendee:</b> " . htmlspecialchars($booking->full_name);
            TelegramService::editMessageText($chatId, $messageId, $alreadyText);
            return;
        }

        TelegramService::answerCallbackQuery($callbackId, "Approval action cancelled.");

        $totalTickets = $booking->adult_count + $booking->child_count + $booking->infant_count;
        $originalText = "🎟️ <b>New Event Ticket Booking</b>\n\n" .
            "📅 <b>Event:</b> " . htmlspecialchars($booking->event->title ?? 'PMCC Event') . "\n" .
            "👤 <b>Booked By:</b> " . htmlspecialchars($booking->full_name) . "\n" .
            "📧 <b>Email:</b> " . htmlspecialchars($booking->email) . "\n" .
            "📱 <b>Phone:</b> " . htmlspecialchars($booking->phone) . "\n" .
            "🎫 <b>Tickets:</b> {$totalTickets} ({$booking->adult_count} Adult, {$booking->child_count} Child, {$booking->infant_count} Infant)\n" .
            "💰 <b>Total Paid:</b> £" . number_format($booking->total_amount, 2);

        $keyboard = [
            [
                ['text' => '✅ Approve Booking', 'callback_data' => "approve_book:{$booking->id}"],
                ['text' => '❌ Decline', 'callback_data' => "decline_book:{$booking->id}"]
            ]
        ];

        TelegramService::editMessageText($chatId, $messageId, $originalText, $keyboard);
    }

    /**
     * Process Booking Decline via Telegram
     */
    protected function processBookingDecline($id, string $callbackId, string $chatId, int $messageId, string $adminName)
    {
        $booking = EventBooking::find($id);
        if (!$booking) {
            TelegramService::answerCallbackQuery($callbackId, "Booking #{$id} not found.", true);
            return;
        }

        if ($booking->booking_status === 'approved') {
            TelegramService::answerCallbackQuery($callbackId, "Booking #{$id} is already APPROVED!", true);
            $alreadyText = "✅ <b>BOOKING ALREADY APPROVED</b>\n\n" .
                "🎟️ <b>Ticket Ref:</b> <code>" . ($booking->reference_no ?? "BOOK-{$booking->id}") . "</code>\n" .
                "👤 <b>Attendee:</b> " . htmlspecialchars($booking->full_name);
            TelegramService::editMessageText($chatId, $messageId, $alreadyText);
            return;
        }

        if ($booking->booking_status === 'cancelled') {
            TelegramService::answerCallbackQuery($callbackId, "Booking #{$id} is already DECLINED!", true);
            return;
        }

        $booking->update(['booking_status' => 'cancelled', 'payment_status' => 'cancelled']);

        TelegramService::answerCallbackQuery($callbackId, "❌ Booking cancelled.", true);

        $newText = "❌ <b>BOOKING DECLINED VIA TELEGRAM</b>\n\n" .
            "👤 <b>Attendee:</b> " . htmlspecialchars($booking->full_name) . "\n" .
            "⚡ <b>Declined By:</b> {$adminName}";

        TelegramService::editMessageText($chatId, $messageId, $newText);
    }

    /**
     * Handle Text Commands & Member Inputs
     */
    protected function handleTextMessage(array $message)
    {
        $text = trim($message['text'] ?? '');
        $chatId = (string) $message['chat']['id'];
        $fromId = $message['from']['id'] ?? $chatId;
        $userName = $message['from']['first_name'] ?? 'User';

        // Command: /start, /menu, menu, help
        if (in_array(strtolower($text), ['/start', '/menu', 'menu', 'help', '/help'])) {
            $this->sendInteractiveMenu($chatId, $userName);
            return;
        }

        // Direct Commands: /approve_mem 104 or /decline_mem 104
        if (preg_match('/^\/approve_mem\s+(\d+)$/i', $text, $matches)) {
            $this->processMemberApproval($matches[1], 'cmd', $chatId, 0, $userName);
            return;
        }
        if (preg_match('/^\/decline_mem\s+(\d+)$/i', $text, $matches)) {
            $this->processMemberDecline($matches[1], 'cmd', $chatId, 0, $userName);
            return;
        }

        // Check if user is in a state flow
        $userState = Cache::get("tg_user_state_{$fromId}");
        if ($userState === 'broadcast') {
            Cache::forget("tg_user_state_{$fromId}");
            TelegramService::sendMessage(
                "📢 <b>PMCC-UK ANNOUNCEMENT</b>\n\n" .
                htmlspecialchars($text) . "\n\n" .
                "<i>Sent via PMCC Admin Control Panel by {$userName}</i>"
            );
            TelegramService::sendMessageToChat($chatId, "✅ <b>Announcement Broadcasted Successfully!</b>");
            return;
        }

        if ($userState === 'download_ticket' || preg_match('/^BOOK-/i', $text)) {
            $this->processTicketInput($text, $fromId, $chatId);
            return;
        }

        if ($userState || preg_match('/^(PMCC-)?\d+$/i', $text)) {
            $this->processMemberIdInput($text, $userState ?? 'download_pdf', $fromId, $chatId);
            return;
        }

        // Default response for unrecognized text
        TelegramService::sendMessageToChat(
            $chatId,
            "👋 Hello <b>{$userName}</b>!\n\nType or send <b>/menu</b> anytime to access the PMCC-UK Interactive Member Bot."
        );
    }

    /**
     * Send Interactive Member Services Menu
     */
    protected function sendInteractiveMenu(string $chatId, string $userName)
    {
        $menuText = "🛡️ <b>PMCC-UK Telegram Admin Control Panel</b>\n\n" .
            "Hello <b>{$userName}</b>! Select an admin control tool from below:";

        $menuButtons = [
            [
                ['text' => '⏳ Pending Approvals Hub', 'callback_data' => 'menu_action:pending_queue'],
                ['text' => '📊 Real-Time Dashboard Stats', 'callback_data' => 'menu_action:admin_stats']
            ],
            [
                ['text' => '🪪 Download Member ID Card', 'callback_data' => 'menu_action:download_pdf'],
                ['text' => '🎟️ Download Event Ticket PDF', 'callback_data' => 'menu_action:download_ticket']
            ],
            [
                ['text' => '🔍 Member / Ticket Lookup', 'callback_data' => 'menu_action:lookup'],
                ['text' => '📄 Export Members CSV', 'callback_data' => 'menu_action:export_members']
            ],
            [
                ['text' => '🎟️ Live Event Check-Ins', 'callback_data' => 'menu_action:check_in_summary'],
                ['text' => '📢 Send Broadcast Alert', 'callback_data' => 'menu_action:broadcast']
            ],
            [
                ['text' => '🚨 View Recent System Logs', 'callback_data' => 'menu_action:system_logs'],
                ['text' => '🌐 Website Maintenance Status', 'callback_data' => 'menu_action:maint_status']
            ],
            [
                ['text' => '❓ Admin Support & Commands', 'callback_data' => 'menu_action:support']
            ]
        ];

        TelegramService::sendMessageToChat($chatId, $menuText, $menuButtons);
    }

    /**
     * Process Event Ticket Download Input (Searches by Membership ID & Active Event ID, or Ticket Ref)
     */
    protected function processTicketInput(string $input, $fromId, string $chatId)
    {
        Cache::forget("tg_user_state_{$fromId}");

        $cleanInput = trim($input);
        $upperInput = strtoupper($cleanInput);
        
        // Extract numeric ID from input string (e.g. BOOK-54 -> 54, BOOK-PMCC-1052-54 -> 54, 54 -> 54)
        $numericId = null;
        if (preg_match('/(\d+)$/', $cleanInput, $matches)) {
            $numericId = $matches[1];
        }

        $formattedMemId = $numericId ? "PMCC-{$numericId}" : $upperInput;

        // Get Active / Upcoming Event ID
        $activeEvent = \App\Models\Event::orderBy('event_date', 'desc')->first();
        $activeEventId = $activeEvent ? $activeEvent->id : null;

        $booking = null;

        // 1. Search by numeric booking ID (primary key id)
        if ($numericId) {
            $booking = EventBooking::find($numericId);
        }

        // 2. Search by Membership ID + Active Event ID
        if (!$booking && $activeEventId) {
            $booking = EventBooking::where('event_id', $activeEventId)
                ->where(function($q) use ($upperInput, $formattedMemId, $numericId) {
                    $q->where('membership_no', $upperInput)
                      ->orWhere('membership_no', $formattedMemId);
                    if ($numericId) {
                        $q->orWhere('membership_no', $numericId);
                    }
                })
                ->first();
        }

        // 3. Search by Membership ID across all events (latest booking)
        if (!$booking) {
            $booking = EventBooking::where(function($q) use ($upperInput, $formattedMemId, $numericId) {
                $q->where('membership_no', $upperInput)
                  ->orWhere('membership_no', $formattedMemId);
                if ($numericId) {
                    $q->orWhere('membership_no', $numericId);
                }
            })
            ->latest('id')
            ->first();
        }

        // 4. Map member table -> assigned membership_no -> event booking
        if (!$booking) {
            $memberObj = Member::where(function($q) use ($upperInput, $formattedMemId) {
                $q->where('membership_id_assigned', $upperInput)
                  ->orWhere('membership_id_assigned', $formattedMemId)
                  ->orWhere('prev_membership_no', $upperInput)
                  ->orWhere('prev_membership_no', $formattedMemId);
            })->first();

            if (!$memberObj && $numericId) {
                $memberObj = Member::where('id', $numericId)->first();
            }

            if ($memberObj) {
                $assignedNo = $memberObj->membership_id_assigned ?: "PMCC-{$memberObj->id}";
                $booking = EventBooking::where(function($q) use ($assignedNo, $memberObj) {
                    $q->where('membership_no', $assignedNo)
                      ->orWhere('membership_no', "PMCC-{$memberObj->id}")
                      ->orWhere('membership_no', (string)$memberObj->id);
                })
                ->when($activeEventId, function($q) use ($activeEventId) {
                    $q->orderByRaw("event_id = {$activeEventId} DESC");
                })
                ->first();
            }
        }

        $refNo = $booking->reference_no ?: ("BOOK-" . ($booking->membership_no != 'NON-MEMBER' ? $booking->membership_no : 'NM') . "-" . $booking->id);

        // Check if Booking Status is Approved
        if ($booking->booking_status !== 'approved') {
            $statusStr = strtoupper($booking->booking_status ?: 'PENDING');
            TelegramService::sendMessageToChat(
                $chatId,
                "⚠️ <b>Event Ticket Not Approved</b>\n\n" .
                "🎟️ <b>Ticket Ref:</b> <code>{$refNo}</code>\n" .
                "👤 <b>Attendee:</b> " . htmlspecialchars($booking->full_name) . "\n" .
                "📅 <b>Event:</b> " . htmlspecialchars($booking->event->title ?? 'PMCC Event') . "\n" .
                "⚠️ <b>Current Status:</b> <b>{$statusStr}</b>\n\n" .
                "<i>Entry tickets can only be downloaded after your event booking has been approved by PMCC-UK admins.</i>"
            );
            return;
        }

        try {
            TelegramService::sendMessageToChat($chatId, "⏳ <i>Generating official PMCC-UK Event Ticket PDF for {$refNo}...</i>");

            $pdf = Pdf::loadView('events.pdf_ticket', compact('booking'))
                ->setPaper('a5', 'portrait')
                ->setOption('isRemoteEnabled', true);

            $tempPath = storage_path("app/PMCC_Event_Ticket_{$booking->id}.pdf");
            $pdf->save($tempPath);

            $filename = "PMCC_Event_Ticket_{$refNo}.pdf";
            $caption = "🎟️ <b>Official PMCC-UK Event Entry Ticket</b>\n\n" .
                "📅 <b>Event:</b> " . htmlspecialchars($booking->event->title ?? 'PMCC Event') . "\n" .
                "👤 <b>Attendee:</b> " . htmlspecialchars($booking->full_name) . "\n" .
                "🎟️ <b>Ref:</b> <code>{$refNo}</code>";

            TelegramService::sendDocument($chatId, $tempPath, $filename, $caption);

            if (file_exists($tempPath)) unlink($tempPath);
        } catch (\Exception $e) {
            Log::error("Telegram Ticket PDF Generation Failed: " . $e->getMessage());
            TelegramService::sendMessageToChat($chatId, "❌ <b>Ticket PDF Error</b>: " . htmlspecialchars($e->getMessage()));
        }
    }

    /**
     * Process Membership ID Input for ID Card PDF Generation / Status Check
     */
    protected function processMemberIdInput(string $input, string $state, $fromId, string $chatId)
    {
        Cache::forget("tg_user_state_{$fromId}");

        $cleanInput = strtoupper(trim($input));
        $formattedId = is_numeric($cleanInput) ? "PMCC-{$cleanInput}" : $cleanInput;
        $rawNumeric = is_numeric($cleanInput) ? $cleanInput : preg_replace('/[^0-9]/', '', $cleanInput);

        // 1. Search primarily by assigned membership registration number or previous membership number
        $member = Member::where(function($q) use ($cleanInput, $formattedId) {
            $q->where('membership_id_assigned', $formattedId)
              ->orWhere('membership_id_assigned', $cleanInput)
              ->orWhere('prev_membership_no', $formattedId)
              ->orWhere('prev_membership_no', $cleanInput);
        })->first();

        // 2. Fallback: Search by DB auto-increment ID only if no assigned/prev registration number matched
        if (!$member && !empty($rawNumeric)) {
            $member = Member::where('id', $rawNumeric)->first();
        }

        if (!$member) {
            TelegramService::sendMessageToChat(
                $chatId,
                "❌ <b>Membership Not Found</b>\n\nNo member record matching <code>{$cleanInput}</code> was found. Please check your Membership ID and try again."
            );
            return;
        }

        $regNo = $member->membership_id_assigned ?: ($member->prev_membership_no ?: "PMCC-{$member->id}");
        $validTill = $member->expiry_date ? Carbon::parse($member->expiry_date)->format('d M Y') : 'N/A';

        if ($member->status !== 'active') {
            TelegramService::sendMessageToChat(
                $chatId,
                "⚠️ <b>Membership Inactive</b>\n\n" .
                "👤 <b>Name:</b> " . htmlspecialchars($member->full_name) . "\n" .
                "🆔 <b>Reg No:</b> <code>{$regNo}</code>\n" .
                "⚠️ <b>Current Status:</b> <b>" . strtoupper($member->status) . "</b>\n\n" .
                "<i>Your membership is pending approval. ID card download will be available once approved.</i>"
            );
            return;
        }

        if ($state === 'check_status' || $state === 'check_expiry') {
            TelegramService::sendMessageToChat(
                $chatId,
                "✅ <b>PMCC-UK Membership Record</b>\n\n" .
                "👤 <b>Member Name:</b> " . htmlspecialchars($member->full_name) . "\n" .
                "🆔 <b>Assigned Reg No:</b> <code>{$regNo}</code>\n" .
                "💳 <b>Membership Type:</b> " . htmlspecialchars($member->membership_type) . "\n" .
                "📅 <b>Expiry Date:</b> {$validTill}\n" .
                "✅ <b>Account Status:</b> ACTIVE"
            );
            return;
        }

        // Default / download_pdf Action: Generate and Send PDF
        try {
            TelegramService::sendMessageToChat($chatId, "⏳ <i>Generating official PMCC-UK Membership ID Card PDF for {$regNo}...</i>");

            $pdf = Pdf::loadView('admin.members.pdf_card', compact('member'))
                ->setPaper('a5', 'landscape')
                ->setOption('isRemoteEnabled', true)
                ->setOption('isHtml5ParserEnabled', true);

            $tempPath = storage_path("app/PMCC_ID_Card_{$member->id}.pdf");
            $pdf->save($tempPath);

            $filename = "PMCC_ID_Card_{$regNo}.pdf";
            $caption = "✅ <b>Official PMCC-UK Membership ID Card</b>\n\n" .
                "👤 <b>Member:</b> " . htmlspecialchars($member->full_name) . "\n" .
                "🆔 <b>Reg No:</b> <code>{$regNo}</code>\n" .
                "📅 <b>Valid Until:</b> {$validTill}";

            TelegramService::sendDocument($chatId, $tempPath, $filename, $caption);

            if (file_exists($tempPath)) unlink($tempPath);
        } catch (\Exception $e) {
            Log::error("Telegram PDF Generation Failed: " . $e->getMessage());
            TelegramService::sendMessageToChat($chatId, "❌ <b>PDF Generation Error</b>: " . htmlspecialchars($e->getMessage()));
        }
    }
}
