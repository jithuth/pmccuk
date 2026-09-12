<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Member;
use App\Models\EventBooking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenWaService
{
    /**
     * Cache for settings
     */
    protected static ?array $settingsCache = null;
    protected static bool $isSendingAdminAlert = false;

    /**
     * Retrieve a WhatsApp setting with fallback
     */
    public static function getSetting(string $key, $default = null)
    {
        if (self::$settingsCache === null) {
            self::$settingsCache = [];
            try {
                $rows = Setting::where('setting_key', 'LIKE', 'whatsapp_%')->get();
                foreach ($rows as $row) {
                    self::$settingsCache[$row->setting_key] = $row->setting_value;
                }
            } catch (\Throwable $e) {
                // DB not ready or table missing
            }
        }

        return self::$settingsCache[$key] ?? config("services.whatsapp.{$key}", $default);
    }

    /**
     * Check if WhatsApp Automation is enabled globally
     */
    public static function isEnabled(): bool
    {
        return (string) self::getSetting('whatsapp_enabled', '0') === '1';
    }

    /**
     * Get Server Base URL (e.g., http://127.0.0.1:8085)
     */
    public static function getServerUrl(): string
    {
        $url = (string) self::getSetting('whatsapp_server_url', 'http://127.0.0.1:8085');
        return rtrim($url, '/');
    }

    /**
     * Get Bearer API Key
     */
    public static function getApiKey(): string
    {
        return (string) self::getSetting('whatsapp_api_key', 'pmcc_wa_sec_key_2026_x9');
    }

    /**
     * Format a phone number to standard international format (UK: 447...)
     * Handles numbers starting with 0, +44, 44, or without country code.
     */
    public static function formatPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $trimmed = trim($phone);

        // If it's already a full WhatsApp JID (e.g. 275767166550158@lid or 447901296858@s.whatsapp.net), preserve it untouched!
        if (str_contains($trimmed, '@')) {
            return $trimmed;
        }

        // Strip non-digit characters
        $clean = preg_replace('/[^0-9]/', '', $trimmed);
        if (empty($clean)) {
            return null;
        }

        // UK Local: 07xxxxxxxxx (11 digits) -> 447xxxxxxxxx
        if (str_starts_with($clean, '0') && strlen($clean) === 11) {
            $clean = '44' . substr($clean, 1);
        } elseif (str_starts_with($clean, '440') && strlen($clean) === 13) {
            // Accidentally typed 4407xxxxxxxxx -> 447xxxxxxxxx
            $clean = '44' . substr($clean, 3);
        } elseif (str_starts_with($clean, '0044')) {
            // 00447xxxxxxxxx -> 447xxxxxxxxx
            $clean = substr($clean, 2);
        } elseif (strlen($clean) === 10) {
            // Number entered without country code (e.g. 7901296858): add +44
            $clean = '44' . $clean;
        }

        return $clean;
    }

    /**
     * Find an active Member strictly matching a WhatsApp phone number or membership ID.
     * Searches database variants: 07..., 447..., +447..., formatted with spaces, and smart decryption.
     */
    public static function findMemberByPhone(?string $phone): ?Member
    {
        if (empty($phone)) {
            return null;
        }

        $trimmed = trim($phone);

        // 1. Direct search by explicit Membership ID if passed
        if (preg_match('/PMCC-?\d+/i', $trimmed, $m)) {
            $mem = Member::where('membership_id_assigned', 'LIKE', "%{$m[0]}%")
                ->where('status', 'active')
                ->first();
            if ($mem) return $mem;
        }

        // 2. Normalize phone to canonical UK digits (e.g. 447901296858)
        $clean = self::formatPhone($trimmed);
        if (empty($clean) || strlen($clean) < 8) {
            return null;
        }

        // Build UK representation variants
        $variants = [$clean];
        if (str_starts_with($clean, '44') && strlen($clean) === 12) {
            $national10 = substr($clean, 2);   // '7901296858'
            $local11 = '0' . $national10;       // '07901296858'
            $plusIntl = '+' . $clean;           // '+447901296858'
            $spaced5_6 = substr($local11, 0, 5) . ' ' . substr($local11, 5); // '07901 296858'
            $spaced4_3_4 = substr($local11, 0, 4) . ' ' . substr($local11, 4, 3) . ' ' . substr($local11, 7);
            
            $variants = array_unique([$clean, $plusIntl, $local11, $national10, $spaced5_6, $spaced4_3_4]);
        }

        // 3. Fast database query across all variants
        $member = Member::where('status', 'active')
            ->where(function ($q) use ($variants) {
                foreach ($variants as $v) {
                    $q->orWhere('mobile_number', $v)
                      ->orWhere('mobile_number', 'LIKE', "%{$v}%");
                }
            })
            ->first();

        if ($member) {
            return $member;
        }

        // 4. Fallback for models with encrypted mobile_number (HasSmartDecryption)
        // Scan active members and compare normalized mobile numbers
        $activeMembers = Member::where('status', 'active')
            ->whereNotNull('mobile_number')
            ->get();

        foreach ($activeMembers as $m) {
            $mClean = self::formatPhone($m->mobile_number);
            if ($mClean && $mClean === $clean) {
                return $m;
            }
        }

        return null;
    }

    /**
     * Fetch connection status from WhatsApp microservice daemon
     */
    public static function getStatus(): array
    {
        try {
            $response = Http::timeout(4)
                ->withToken(self::getApiKey())
                ->get(self::getServerUrl() . '/session/status');

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'status' => 'offline',
                'connected' => false,
                'hasQr' => false,
                'message' => 'Daemon returned HTTP ' . $response->status()
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 'offline',
                'connected' => false,
                'hasQr' => false,
                'message' => 'Cannot connect to daemon at ' . self::getServerUrl() . ': ' . $e->getMessage()
            ];
        }
    }

    /**
     * Fetch live QR code (Base64 data URL)
     */
    public static function getQr(): ?string
    {
        try {
            $response = Http::timeout(4)
                ->withToken(self::getApiKey())
                ->get(self::getServerUrl() . '/session/qr');

            if ($response->successful()) {
                $data = $response->json();
                return $data['qr'] ?? null;
            }
        } catch (\Throwable $e) {
            Log::warning('[WhatsApp] Failed to fetch QR: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Disconnect and logout the active session
     */
    public static function logout(): bool
    {
        try {
            $response = Http::timeout(6)
                ->withToken(self::getApiKey())
                ->post(self::getServerUrl() . '/session/logout');

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('[WhatsApp] Logout error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke session and purge credentials (supports force purge)
     */
    public static function revoke(bool $force = false): array
    {
        try {
            $response = Http::timeout(8)
                ->withToken(self::getApiKey())
                ->post(self::getServerUrl() . '/session/revoke', [
                    'force' => $force
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json('message') ?? 'WhatsApp session revoked successfully.'
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('error') ?? 'Failed to revoke WhatsApp session from daemon.'
            ];
        } catch (\Throwable $e) {
            Log::error('[WhatsApp] Revoke error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Connection error contacting WhatsApp daemon: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Fetch Live Gateway & Bot Logs from daemon, with fallback to Laravel logs
     */
    public static function getLogs(int $limit = 100, ?string $type = null, ?int $sinceId = null, ?string $search = null): array
    {
        $params = ['limit' => $limit];
        if ($type) $params['type'] = $type;
        if ($sinceId !== null) $params['since_id'] = $sinceId;
        if ($search) $params['search'] = $search;

        try {
            $response = Http::timeout(4)
                ->withToken(self::getApiKey())
                ->get(self::getServerUrl() . '/logs', $params);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::warning('[WhatsApp] Daemon log fetch failed, falling back to local logs: ' . $e->getMessage());
        }

        // Fallback: extract WhatsApp entries from storage/logs/laravel.log
        return self::getLocalLaravelWhatsAppLogs($limit, $search);
    }

    /**
     * Clear daemon and local log buffer
     */
    public static function clearLogs(): bool
    {
        try {
            $response = Http::timeout(4)
                ->withToken(self::getApiKey())
                ->delete(self::getServerUrl() . '/logs');

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('[WhatsApp] Clear logs error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Parse local Laravel log file for WhatsApp-related entries as fallback
     */
    protected static function getLocalLaravelWhatsAppLogs(int $limit = 50, ?string $search = null): array
    {
        $logFile = storage_path('logs/laravel.log');
        if (!file_exists($logFile)) {
            return ['success' => true, 'count' => 0, 'logs' => []];
        }

        $lines = @file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return ['success' => true, 'count' => 0, 'logs' => []];
        }

        $waLines = [];
        $id = 1;
        foreach (array_reverse($lines) as $line) {
            if (str_contains($line, '[WhatsApp') || str_contains($line, 'whatsapp') || str_contains($line, 'WhatsAppWebhookController')) {
                if ($search && !str_contains(strtolower($line), strtolower($search))) {
                    continue;
                }

                $level = 'INFO';
                if (str_contains($line, '.ERROR')) $level = 'ERROR';
                elseif (str_contains($line, '.WARNING')) $level = 'WARNING';
                elseif (str_contains($line, 'sent successfully') || str_contains($line, 'connected')) $level = 'SUCCESS';

                $type = 'system';
                if (str_contains($line, 'Inbound') || str_contains($line, 'Received')) $type = 'inbound';
                elseif (str_contains($line, 'sent') || str_contains($line, 'dispatched') || str_contains($line, 'File')) $type = 'outbound';

                // Extract timestamp if present [YYYY-MM-DD HH:MM:SS]
                $ts = now()->toISOString();
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m)) {
                    $ts = $m[1];
                }

                // Extract clean message
                $msg = preg_replace('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\].*?(local\.\w+: )?/', '', $line);

                $waLines[] = [
                    'id' => $id++,
                    'timestamp' => $ts,
                    'type' => $type,
                    'level' => $level,
                    'message' => trim($msg),
                    'details' => ['source' => 'laravel.log']
                ];

                if (count($waLines) >= $limit) {
                    break;
                }
            }
        }

        return [
            'success' => true,
            'count' => count($waLines),
            'total_available' => count($waLines),
            'last_id' => count($waLines),
            'logs' => array_reverse($waLines)
        ];
    }

    /**
     * Send Plain Text Message
     */
    public static function sendText(string $phone, string $message): bool
    {
        if (!self::isEnabled()) {
            Log::info("[WhatsApp] Skipping dispatch to {$phone}: WhatsApp automation is disabled.");
            return false;
        }

        $formatted = self::formatPhone($phone);
        if (!$formatted) {
            Log::warning("[WhatsApp] Invalid phone number provided: {$phone}");
            return false;
        }

        try {
            $response = Http::timeout(8)
                ->withToken(self::getApiKey())
                ->post(self::getServerUrl() . '/send/text', [
                    'to' => $formatted,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                Log::info("[WhatsApp] Text message sent successfully to {$formatted}");
                return true;
            }

            Log::error("[WhatsApp] Failed to send text to {$formatted}: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("[WhatsApp] Exception sending text to {$formatted}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send File / Document / PDF with optional caption
     */
    public static function sendFile(string $phone, string $filePathOrBase64, string $filename, ?string $caption = null, string $mimetype = 'application/pdf'): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $formatted = self::formatPhone($phone);
        if (!$formatted) {
            return false;
        }

        $base64 = $filePathOrBase64;
        if (file_exists($filePathOrBase64)) {
            $fileData = file_get_contents($filePathOrBase64);
            if ($fileData === false) {
                Log::error("[WhatsApp] Cannot read file at {$filePathOrBase64}");
                return false;
            }
            $base64 = base64_encode($fileData);
        }

        try {
            $response = Http::timeout(15)
                ->withToken(self::getApiKey())
                ->post(self::getServerUrl() . '/send/file', [
                    'to' => $formatted,
                    'base64' => $base64,
                    'filename' => $filename,
                    'caption' => $caption,
                    'mimetype' => $mimetype,
                ]);

            if ($response->successful()) {
                Log::info("[WhatsApp] File {$filename} sent to {$formatted}");
                return true;
            }

            Log::error("[WhatsApp] Failed to send file to {$formatted}: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("[WhatsApp] Exception sending file to {$formatted}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send Contact Card (vCard)
     */
    public static function sendContact(string $phone, string $contactName, string $contactPhone, ?string $role = null): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $formatted = self::formatPhone($phone);
        if (!$formatted) {
            return false;
        }

        try {
            $response = Http::timeout(8)
                ->withToken(self::getApiKey())
                ->post(self::getServerUrl() . '/send/contact', [
                    'to' => $formatted,
                    'name' => $contactName,
                    'phone' => $contactPhone,
                    'role' => $role
                ]);

            if ($response->successful()) {
                Log::info("[WhatsApp] Contact {$contactName} ({$contactPhone}) sent to {$formatted}");
                return true;
            }

            Log::error("[WhatsApp] Failed to send contact to {$formatted}: " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error("[WhatsApp] Exception sending contact to {$formatted}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Dispatch a complete multi-attachment broadcast bundle to a recipient:
     * - Main personalized text message with any attached URLs
     * - Multiple images (with caption)
     * - Multiple documents (PDF, Docx, etc.)
     * - Multiple contact cards (vCards)
     */
    public static function dispatchBroadcastBundle(string $recipientPhone, string $recipientName, string $message, array $attachments = []): bool
    {
        $formatted = self::formatPhone($recipientPhone);
        if (!$formatted) {
            return false;
        }

        // 1. Personalize text and append URL blocks if present
        $body = str_replace('{name}', $recipientName, $message);

        $urls = $attachments['urls'] ?? [];
        if (!empty($urls) && is_array($urls)) {
            $urlText = "\n";
            foreach ($urls as $u) {
                $label = !empty($u['title']) ? "*{$u['title']}*: " : '';
                $urlText .= "\n🔗 {$label}{$u['url']}";
            }
            if (!str_contains($body, trim($urlText))) {
                $body .= $urlText;
            }
        }

        // Send main text message
        $mainOk = self::sendText($formatted, $body);
        if (!$mainOk) {
            // Even if text failed, try sending media
        }

        // 2. Dispatch multiple images
        $images = $attachments['images'] ?? [];
        if (!empty($images) && is_array($images)) {
            foreach ($images as $img) {
                usleep(350000); // 350ms delay
                $absPath = $img['path'] ?? null;
                if ($absPath && file_exists($absPath)) {
                    $caption = $img['caption'] ?? null;
                    $mime = $img['mimetype'] ?? 'image/jpeg';
                    $name = $img['name'] ?? 'broadcast_image.jpg';
                    self::sendFile($formatted, $absPath, $name, $caption, $mime);
                }
            }
        }

        // 3. Dispatch multiple documents
        $docs = $attachments['documents'] ?? [];
        if (!empty($docs) && is_array($docs)) {
            foreach ($docs as $doc) {
                usleep(350000); // 350ms delay
                $absPath = $doc['path'] ?? null;
                if ($absPath && file_exists($absPath)) {
                    $caption = $doc['caption'] ?? null;
                    $mime = $doc['mimetype'] ?? 'application/pdf';
                    $name = $doc['name'] ?? 'document.pdf';
                    self::sendFile($formatted, $absPath, $name, $caption, $mime);
                }
            }
        }

        // 4. Dispatch multiple contacts
        $contacts = $attachments['contacts'] ?? [];
        if (!empty($contacts) && is_array($contacts)) {
            foreach ($contacts as $contact) {
                usleep(350000); // 350ms delay
                $cName = $contact['name'] ?? 'Executive Member';
                $cPhone = $contact['phone'] ?? null;
                $cRole = $contact['role'] ?? 'Executive Committee';
                if ($cPhone) {
                    self::sendContact($formatted, $cName, $cPhone, $cRole);
                }
            }
        }

        return true;
    }

    /**
     * 1. Member ID Card Delivery Notification (Automated PDF)
     */
    public static function notifyMemberIdCard(Member $member, ?string $pdfPath = null, ?string $targetRecipient = null): bool
    {
        if ((string) self::getSetting('whatsapp_notify_id_card', '1') !== '1') {
            return false;
        }

        $phone = $targetRecipient ?: $member->mobile_number;
        if (empty($phone)) {
            return false;
        }

        $caption = "🌟 *Welcome to PMCC-UK Membership!*\n\n" .
            "Dear *{$member->full_name}*,\n\n" .
            "We are pleased to inform you that your membership for the **Plymouth Malayalee Community Club (PMCC-UK)** is active!\n\n" .
            "🆔 **Membership No:** `{$member->membership_id_assigned}`\n" .
            "📅 **Valid Through:** " . ($member->expiry_date ? date('d M Y', strtotime($member->expiry_date)) : date('31 Dec Y')) . "\n" .
            "📍 **Community:** Plymouth & Surrounding Devon Regions\n\n" .
            "Please find your official **PMCC-UK Digital Membership Card** attached.\n\n" .
            "_Thank you for being an esteemed part of our community!_\n" .
            "🌐 https://pmccuk.org";

        // Auto-generate PDF card if not explicitly provided
        $tempGenerated = false;
        if (!$pdfPath || !file_exists($pdfPath)) {
            try {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.members.pdf_card', compact('member'));
                $tempPath = storage_path('app/temp_card_' . $member->id . '_' . time() . '.pdf');
                $pdf->save($tempPath);
                $pdfPath = $tempPath;
                $tempGenerated = true;
            } catch (\Throwable $e) {
                Log::warning('[WhatsApp] Could not generate member card PDF: ' . $e->getMessage());
            }
        }

        $result = false;
        if ($pdfPath && file_exists($pdfPath)) {
            $result = self::sendFile($phone, $pdfPath, "PMCC_Card_{$member->membership_id_assigned}.pdf", $caption, 'application/pdf');
            if ($tempGenerated && file_exists($pdfPath)) {
                @unlink($pdfPath);
            }
        } else {
            $result = self::sendText($phone, $caption);
        }

        return $result;
    }

    /**
     * 2. Event Ticket PDF & Confirmation Notification (Automated PDF)
     */
    public static function notifyEventTicket(EventBooking $booking, ?string $ticketPdfPath = null, ?string $targetRecipient = null): bool
    {
        if ((string) self::getSetting('whatsapp_notify_event_ticket', '1') !== '1') {
            return false;
        }

        $phone = $targetRecipient ?: $booking->phone;
        if (empty($phone)) {
            return false;
        }

        $eventTitle = $booking->event ? $booking->event->title : 'PMCC Event';
        $eventDate = $booking->event && $booking->event->event_date ? date('D, d M Y', strtotime($booking->event->event_date)) : 'Upcoming';
        $eventVenue = $booking->event ? ($booking->event->venue ?? 'Plymouth, UK') : 'Plymouth, UK';

        $caption = "🎟️ *PMCC-UK Event Ticket Confirmed!*\n\n" .
            "Dear *{$booking->customer_name}*,\n\n" .
            "Your ticket reservation for *{$eventTitle}* has been confirmed.\n\n" .
            "🔖 **Booking Ref:** `{$booking->reference}`\n" .
            "📅 **Date:** {$eventDate}\n" .
            "📍 **Venue:** {$eventVenue}\n" .
            "👥 **Total Tickets:** {$booking->total_tickets}\n" .
            "💳 **Amount Paid:** £" . number_format($booking->total_amount ?? 0, 2) . "\n\n" .
            "Your official digital pass with admission QR code is attached.\n" .
            "_Please present this ticket at the gate control counter for entry scan._\n\n" .
            "Warm regards,\n*PMCC-UK Events Team*";

        // Auto-generate PDF ticket if not explicitly provided
        $tempGenerated = false;
        if (!$ticketPdfPath || !file_exists($ticketPdfPath)) {
            try {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('events.pdf_ticket', compact('booking'));
                $tempPath = storage_path('app/temp_ticket_' . $booking->id . '_' . time() . '.pdf');
                $pdf->save($tempPath);
                $ticketPdfPath = $tempPath;
                $tempGenerated = true;
            } catch (\Throwable $e) {
                Log::warning('[WhatsApp] Could not generate event ticket PDF: ' . $e->getMessage());
            }
        }

        $result = false;
        if ($ticketPdfPath && file_exists($ticketPdfPath)) {
            $result = self::sendFile($phone, $ticketPdfPath, "Ticket_{$booking->reference}.pdf", $caption, 'application/pdf');
            if ($tempGenerated && file_exists($ticketPdfPath)) {
                @unlink($ticketPdfPath);
            }
        } else {
            $result = self::sendText($phone, $caption);
        }

        return $result;
    }

    /**
     * 3. 24-Hour Event Reminder & Venue Navigation
     */
    public static function notifyEventReminder(EventBooking $booking): bool
    {
        $phone = $booking->phone;
        if (empty($phone)) {
            return false;
        }

        $eventTitle = $booking->event ? $booking->event->title : 'PMCC Event';
        $eventDate = $booking->event && $booking->event->event_date ? date('D, d M Y', strtotime($booking->event->event_date)) : 'Tomorrow';
        $eventTime = $booking->event && $booking->event->event_time ? date('h:i A', strtotime($booking->event->event_time)) : 'Doors open early';
        $eventVenue = $booking->event ? ($booking->event->venue ?? 'Plymouth, UK') : 'Plymouth, UK';
        $mapsUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($eventVenue);

        $message = "⏰ *Event Reminder: Tomorrow!*\n\n" .
            "Dear *{$booking->customer_name}*,\n\n" .
            "We are excited to welcome you tomorrow for *{$eventTitle}*!\n\n" .
            "📅 **Date:** {$eventDate}\n" .
            "🕒 **Time:** {$eventTime}\n" .
            "📍 **Venue:** {$eventVenue}\n" .
            "🗺️ **Directions / Map:** {$mapsUrl}\n" .
            "🔖 **Your Booking Ref:** `{$booking->reference}` (Total: {$booking->total_tickets})\n\n" .
            "💡 *Tip:* Please have your ticket QR code or booking reference handy for swift admission at our gate counter.\n\n" .
            "See you tomorrow!\n*PMCC-UK Executive Team*";

        return self::sendText($phone, $message);
    }

    /**
     * 4. Annual Renewal Reminder (30-day notice)
     */
    public static function notifyRenewalReminder(Member $member): bool
    {
        $phone = $member->mobile_number;
        if (empty($phone)) {
            return false;
        }

        $expiry = $member->expiry_date ? date('d M Y', strtotime($member->expiry_date)) : '31 Dec ' . date('Y');

        $message = "🔔 *PMCC-UK Membership Renewal Notice*\n\n" .
            "Dear *{$member->full_name}*,\n\n" .
            "Your annual PMCC-UK Membership (`{$member->membership_id_assigned}`) is scheduled to expire on *{$expiry}*.\n\n" .
            "To retain your member benefits, discounted event ticketing, and community privileges, please renew your membership online:\n\n" .
            "👉 *Renew Online:* https://pmccuk.org/membership\n\n" .
            "Thank you for being a vital pillar of our community!\n\n" .
            "*PMCC-UK Executive Committee*";

        return self::sendText($phone, $message);
    }

    /**
     * 5. Send Verification / Login / Registration OTP
     */
    public static function sendOtp(string $phone, string $otp, string $purpose = 'Registration'): bool
    {
        if ((string) self::getSetting('whatsapp_notify_otp', '1') !== '1') {
            return false;
        }

        $message = "🔐 *PMCC-UK Verification Code*\n\n" .
            "Your 6-digit verification code for *{$purpose}* is:\n\n" .
            "👉 *{$otp}*\n\n" .
            "⚠️ This code is confidential and expires in 10 minutes. Please do not share it with anyone.\n\n" .
            "PMCC-UK Secure Services";

        return self::sendText($phone, $message);
    }

    /**
     * 6. Send Security / Intrusion / Event Alert to Administrators (Mirrors Telegram alerts)
     */
    public static function sendAdminAlert(string $titleOrMessage, ?string $alertDetails = null): bool
    {
        if (self::$isSendingAdminAlert) {
            return false;
        }

        if (!self::isEnabled()) {
            return false;
        }

        $notifyEnabled = (string) self::getSetting('whatsapp_notify_admin_security', '1') === '1';
        if (!$notifyEnabled) {
            return false;
        }

        $rawNumbers = (string) self::getSetting('whatsapp_admin_numbers', '');
        if (empty($rawNumbers)) {
            return false;
        }

        $numbers = array_filter(array_map('trim', preg_split('/[,\n;]+/', $rawNumbers)));
        if (empty($numbers)) {
            return false;
        }

        if ($alertDetails !== null) {
            $message = "🚨 *PMCC-UK System Alert*\n\n" .
                "⚠️ *{$titleOrMessage}*\n\n" .
                "{$alertDetails}\n\n" .
                "🕒 " . date('d M Y H:i:s') . " UTC";
        } else {
            $message = self::convertHtmlToWhatsAppMarkdown($titleOrMessage);
        }

        self::$isSendingAdminAlert = true;
        $sentCount = 0;

        try {
            foreach ($numbers as $num) {
                if (!empty($num)) {
                    if (self::sendText($num, $message)) {
                        $sentCount++;
                    }
                }
            }
        } finally {
            self::$isSendingAdminAlert = false;
        }

        return $sentCount > 0;
    }

    /**
     * Send Admin Photo Alert (e.g. Webcam Intrusion / Login Capture)
     */
    public static function sendAdminPhotoAlert(string $photoPath, string $caption = ''): bool
    {
        if (self::$isSendingAdminAlert || !self::isEnabled() || !file_exists($photoPath)) {
            return false;
        }

        $notifyEnabled = (string) self::getSetting('whatsapp_notify_admin_security', '1') === '1';
        if (!$notifyEnabled) {
            return false;
        }

        $rawNumbers = (string) self::getSetting('whatsapp_admin_numbers', '');
        if (empty($rawNumbers)) {
            return false;
        }

        $numbers = array_filter(array_map('trim', preg_split('/[,\n;]+/', $rawNumbers)));
        if (empty($numbers)) {
            return false;
        }

        $waCaption = self::convertHtmlToWhatsAppMarkdown($caption);
        self::$isSendingAdminAlert = true;
        $sentCount = 0;

        try {
            foreach ($numbers as $num) {
                if (!empty($num)) {
                    if (self::sendFile($num, $photoPath, 'admin_capture.jpg', $waCaption, 'image/jpeg')) {
                        $sentCount++;
                    }
                }
            }
        } finally {
            self::$isSendingAdminAlert = false;
        }

        return $sentCount > 0;
    }

    /**
     * Convert HTML tags (Telegram format) to WhatsApp Markdown
     */
    public static function convertHtmlToWhatsAppMarkdown(string $html): string
    {
        // 1. Bold: <b>text</b>, <strong>text</strong> -> *text*
        $text = preg_replace('/<\s*(?:b|strong)\s*>(.*?)<\s*\/\s*(?:b|strong)\s*>/is', '*$1*', $html);
        
        // 2. Italic: <i>text</i>, <em>text</em> -> _text_
        $text = preg_replace('/<\s*(?:i|em)\s*>(.*?)<\s*\/\s*(?:i|em)\s*>/is', '_$1_', $text);
        
        // 3. Code: <code>text</code> -> ```text```
        $text = preg_replace('/<\s*code\s*>(.*?)<\s*\/\s*code\s*>/is', '```$1```', $text);
        
        // 4. Pre: <pre>text</pre> -> ```\n...\n```
        $text = preg_replace('/<\s*pre\s*>(.*?)<\s*\/\s*pre\s*>/is', "```\n$1\n```", $text);
        
        // 5. Links: <a href="...">text</a> -> text (...)
        $text = preg_replace('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2 ($1)', $text);
        
        // 6. Breaks & Paragraphs
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\s*\/p\s*>/i', "\n\n", $text);
        
        // 7. Strip any remaining tags
        $text = strip_tags($text);
        
        // 8. Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // 9. Clean up excess empty lines
        $text = preg_replace("/\n{3,}/", "\n\n", trim($text));

        return $text;
    }

    /**
     * 7. Targeted Batch Broadcast with Anti-Spam Throttling
     */
    public static function sendBroadcast(array $recipients, string $message, ?string $filePath = null, ?string $filename = null, int $delaySeconds = 1): array
    {
        $sent = 0;
        $failed = 0;
        $total = count($recipients);

        Log::info("[WhatsApp Broadcast] Starting broadcast dispatch to {$total} recipients.");

        foreach ($recipients as $item) {
            $phone = is_array($item) ? ($item['phone'] ?? null) : $item;
            if (empty($phone)) {
                $failed++;
                continue;
            }

            // Optional personalizations
            $personalized = $message;
            if (is_array($item) && !empty($item['name'])) {
                $personalized = str_replace('{name}', $item['name'], $personalized);
            }

            $success = false;
            if ($filePath && file_exists($filePath)) {
                $success = self::sendFile($phone, $filePath, $filename ?? 'attachment.pdf', $personalized);
            } else {
                $success = self::sendText($phone, $personalized);
            }

            if ($success) {
                $sent++;
            } else {
                $failed++;
            }

            // Anti-spam rate limiting interval
            if ($delaySeconds > 0) {
                sleep($delaySeconds);
            }
        }

        Log::info("[WhatsApp Broadcast] Completed: {$sent} sent, {$failed} failed.");

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed
        ];
    }
}
