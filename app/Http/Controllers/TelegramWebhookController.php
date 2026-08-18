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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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

        // 2. Handle Text Message Commands (e.g. /approve_mem 123 or /decline_mem 123)
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
        $adminName = $callbackQuery['from']['first_name'] ?? 'Admin';

        if (empty($data)) {
            TelegramService::answerCallbackQuery($callbackId, "No action payload.");
            return;
        }

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
                $this->processBookingApproval($id, $callbackId, $chatId, $messageId, $adminName);
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

        // Determine assigned membership number
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

        // Record financial transaction
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

        // Record activity log
        try {
            ActivityLog::create([
                'user_type' => 'telegram_admin',
                'action' => 'approve_member_telegram',
                'details' => "Member {$member->full_name} (ID: {$id}) approved via Telegram by {$adminName}. Reg No: {$assignedNo}",
                'ip_address' => 'Telegram Bot API'
            ]);
        } catch (\Exception $e) {}

        // Send ID card email
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
     * Process Member Decline/Rejection via Telegram
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

        // Find member and extend expiry
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
     * Process Booking Approval via Telegram
     */
    protected function processBookingApproval($id, string $callbackId, string $chatId, int $messageId, string $adminName)
    {
        $booking = EventBooking::find($id);
        if (!$booking) {
            TelegramService::answerCallbackQuery($callbackId, "Booking #{$id} not found.", true);
            return;
        }

        $booking->update(['booking_status' => 'approved', 'payment_status' => 'paid', 'check_in_status' => 'confirmed']);

        TelegramService::answerCallbackQuery($callbackId, "✅ Event booking confirmed!", true);

        $newText = "✅ <b>BOOKING CONFIRMED VIA TELEGRAM</b>\n\n" .
            "🎟️ <b>Ticket Ref:</b> <code>" . ($booking->ticket_ref ?? "BOOK-{$booking->id}") . "</code>\n" .
            "👤 <b>Attendee:</b> " . htmlspecialchars($booking->full_name) . "\n" .
            "⚡ <b>Confirmed By:</b> {$adminName}";

        TelegramService::editMessageText($chatId, $messageId, $newText);
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

        $booking->update(['booking_status' => 'cancelled', 'payment_status' => 'cancelled']);

        TelegramService::answerCallbackQuery($callbackId, "❌ Booking cancelled.", true);

        $newText = "❌ <b>BOOKING DECLINED VIA TELEGRAM</b>\n\n" .
            "👤 <b>Attendee:</b> " . htmlspecialchars($booking->full_name) . "\n" .
            "⚡ <b>Declined By:</b> {$adminName}";

        TelegramService::editMessageText($chatId, $messageId, $newText);
    }

    /**
     * Handle Text Commands (e.g. /approve_mem 104 or /decline_mem 104)
     */
    protected function handleTextMessage(array $message)
    {
        $text = trim($message['text'] ?? '');
        $adminName = $message['from']['first_name'] ?? 'Admin';

        if (preg_match('/^\/approve_mem\s+(\d+)$/i', $text, $matches)) {
            $memberId = $matches[1];
            $member = Member::find($memberId);
            if ($member) {
                $assignedNo = "PMCC-" . (1000 + $member->id);
                $expiryDate = Carbon::now()->addYear()->subDay()->format('Y-m-d');
                $member->update(['status' => 'active', 'membership_id_assigned' => $assignedNo, 'expiry_date' => $expiryDate]);
                TelegramService::sendMessage("✅ Member <b>{$member->full_name}</b> approved manually by {$adminName}. Reg No: <code>{$assignedNo}</code>");
            } else {
                TelegramService::sendMessage("❌ Member #{$memberId} not found.");
            }
        } elseif (preg_match('/^\/decline_mem\s+(\d+)$/i', $text, $matches)) {
            $memberId = $matches[1];
            $member = Member::find($memberId);
            if ($member) {
                $member->update(['status' => 'rejected']);
                TelegramService::sendMessage("❌ Member <b>{$member->full_name}</b> registration declined by {$adminName}.");
            } else {
                TelegramService::sendMessage("❌ Member #{$memberId} not found.");
            }
        }
    }
}
