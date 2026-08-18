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
     * Handle Menu Action Click (ID Card Download, Status Check, Expiry Check)
     */
    protected function handleMenuActionClick(string $actionType, $fromId, string $chatId, string $callbackId)
    {
        switch ($actionType) {
            case 'download_pdf':
                Cache::put("tg_user_state_{$fromId}", 'download_pdf', 600);
                TelegramService::answerCallbackQuery($callbackId, "Please enter your Membership ID");
                TelegramService::sendMessageToChat(
                    $chatId,
                    "🪪 <b>Download Membership ID Card (PDF)</b>\n\n" .
                    "Please reply with your <b>Membership ID</b> (e.g. <code>PMCC-1052</code> or <code>1052</code>):"
                );
                break;

            case 'check_status':
                Cache::put("tg_user_state_{$fromId}", 'check_status', 600);
                TelegramService::answerCallbackQuery($callbackId, "Please enter your Membership ID");
                TelegramService::sendMessageToChat(
                    $chatId,
                    "🔍 <b>Check Membership Status</b>\n\n" .
                    "Please reply with your <b>Membership ID</b> (e.g. <code>PMCC-1052</code> or <code>1052</code>):"
                );
                break;

            case 'check_expiry':
                Cache::put("tg_user_state_{$fromId}", 'check_expiry', 600);
                TelegramService::answerCallbackQuery($callbackId, "Please enter your Membership ID");
                TelegramService::sendMessageToChat(
                    $chatId,
                    "📅 <b>Check Expiry Date</b>\n\n" .
                    "Please reply with your <b>Membership ID</b> (e.g. <code>PMCC-1052</code> or <code>1052</code>):"
                );
                break;

            case 'support':
                TelegramService::answerCallbackQuery($callbackId, "PMCC-UK Support");
                TelegramService::sendMessageToChat(
                    $chatId,
                    "🤝 <b>PMCC-UK Support & Inquiries</b>\n\n" .
                    "🌐 <b>Website:</b> https://pmccuk.org\n" .
                    "📧 <b>Email:</b> info@pmccuk.org\n" .
                    "📍 <b>Location:</b> Plymouth, United Kingdom"
                );
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

        // Check if user is in a state flow (e.g. download_pdf, check_status, check_expiry)
        $userState = Cache::get("tg_user_state_{$fromId}");
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
        $menuText = "🤖 <b>PMCC-UK Interactive Member Assistant</b>\n\n" .
            "Hello <b>{$userName}</b>! Select a member action from the menu options below:";

        $menuButtons = [
            [
                ['text' => '🪪 Download ID Card (PDF)', 'callback_data' => 'menu_action:download_pdf']
            ],
            [
                ['text' => '🔍 Check Membership Status', 'callback_data' => 'menu_action:check_status'],
                ['text' => '📅 Check Expiry Date', 'callback_data' => 'menu_action:check_expiry']
            ],
            [
                ['text' => '❓ Contact & Support Info', 'callback_data' => 'menu_action:support']
            ]
        ];

        TelegramService::sendMessageToChat($chatId, $menuText, $menuButtons);
    }

    /**
     * Process Membership ID Input for ID Card PDF Generation / Status Check
     */
    protected function processMemberIdInput(string $input, string $state, $fromId, string $chatId)
    {
        // Clear pending user state
        Cache::forget("tg_user_state_{$fromId}");

        $cleanInput = strtoupper(trim($input));
        $searchId = $cleanInput;
        if (is_numeric($cleanInput)) {
            $searchId = "PMCC-{$cleanInput}";
        }

        $rawNumeric = ltrim($cleanInput, 'PMCC-');

        // Look up member
        $member = Member::where('membership_id_assigned', $searchId)
            ->orWhere('membership_id_assigned', $cleanInput)
            ->orWhere('prev_membership_no', $searchId)
            ->orWhere('id', $rawNumeric)
            ->first();

        if (!$member) {
            TelegramService::sendMessageToChat(
                $chatId,
                "❌ <b>Membership Not Found</b>\n\nNo member record matching <code>{$cleanInput}</code> was found in PMCC-UK records. Please check your Membership ID and try again."
            );
            return;
        }

        $regNo = $member->membership_id_assigned ?: ($member->prev_membership_no ?: "PMCC-{$member->id}");
        $validTill = $member->expiry_date ? Carbon::parse($member->expiry_date)->format('d M Y') : 'N/A';

        // Check if member is Active
        if ($member->status !== 'active') {
            TelegramService::sendMessageToChat(
                $chatId,
                "⚠️ <b>Membership Inactive</b>\n\n" .
                "👤 <b>Name:</b> " . htmlspecialchars($member->full_name) . "\n" .
                "🆔 <b>Reg No:</b> <code>{$regNo}</code>\n" .
                "⚠️ <b>Current Status:</b> <b>" . strtoupper($member->status) . "</b>\n\n" .
                "<i>Your membership application is currently pending approval by PMCC-UK admins. ID card download will be available once approved.</i>"
            );
            return;
        }

        // Handle specific action
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

            // Render PDF via Dompdf
            $pdf = Pdf::loadView('admin.members.pdf_card', compact('member'))
                ->setPaper([0, 0, 396, 252], 'landscape'); // ID card standard dimensions

            $tempPath = storage_path("app/PMCC_ID_Card_{$member->id}.pdf");
            $pdf->save($tempPath);

            $filename = "PMCC_ID_Card_{$regNo}.pdf";
            $caption = "✅ <b>Official PMCC-UK Membership ID Card</b>\n\n" .
                "👤 <b>Member:</b> " . htmlspecialchars($member->full_name) . "\n" .
                "🆔 <b>Reg No:</b> <code>{$regNo}</code>\n" .
                "📅 <b>Valid Until:</b> {$validTill}";

            // Send Document via Telegram
            TelegramService::sendDocument($chatId, $tempPath, $filename, $caption);

            // Clean up temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        } catch (\Exception $e) {
            Log::error("Telegram PDF Generation Failed: " . $e->getMessage());
            TelegramService::sendMessageToChat(
                $chatId,
                "❌ <b>PDF Generation Error</b>: " . htmlspecialchars($e->getMessage())
            );
        }
    }
}
