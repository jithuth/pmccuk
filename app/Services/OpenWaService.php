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
     */
    public static function formatPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Strip non-digit characters
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) {
            return null;
        }

        // UK Local 07xxx -> 447xxx
        if (str_starts_with($clean, '0') && strlen($clean) === 11) {
            $clean = '44' . substr($clean, 1);
        } elseif (str_starts_with($clean, '440')) {
            $clean = '44' . substr($clean, 3);
        }

        // Indian numbers 10 digits -> 91xxx
        if (strlen($clean) === 10 && in_array(substr($clean, 0, 1), ['6', '7', '8', '9'])) {
            $clean = '91' . $clean;
        }

        return $clean;
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
     * 1. Member ID Card Delivery Notification
     */
    public static function notifyMemberIdCard(Member $member, ?string $pdfPath = null): bool
    {
        if ((string) self::getSetting('whatsapp_notify_id_card', '1') !== '1') {
            return false;
        }

        $phone = $member->mobile_number;
        if (empty($phone)) {
            return false;
        }

        $caption = "🌟 *Welcome to PMCC-UK Membership!*\n\n" .
            "Dear *{$member->full_name}*,\n\n" .
            "We are pleased to inform you that your membership for the **Plymouth Malayalee Community Club (PMCC-UK)** has been approved!\n\n" .
            "🆔 **Membership No:** `{$member->membership_id_assigned}`\n" .
            "📅 **Valid Through:** " . date('31 Dec Y') . "\n" .
            "📍 **Community:** Plymouth & Surrounding Devon Regions\n\n" .
            "Please find your official **PMCC-UK Digital Membership Card** attached.\n\n" .
            "_Thank you for being an esteemed part of our community!_\n" .
            "🌐 https://pmccuk.org";

        if ($pdfPath && file_exists($pdfPath)) {
            return self::sendFile($phone, $pdfPath, "PMCC_Card_{$member->membership_id_assigned}.pdf", $caption, 'application/pdf');
        }

        return self::sendText($phone, $caption);
    }

    /**
     * 2. Event Ticket PDF & Confirmation Notification
     */
    public static function notifyEventTicket(EventBooking $booking, ?string $ticketPdfPath = null): bool
    {
        if ((string) self::getSetting('whatsapp_notify_event_ticket', '1') !== '1') {
            return false;
        }

        $phone = $booking->phone;
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

        if ($ticketPdfPath && file_exists($ticketPdfPath)) {
            return self::sendFile($phone, $ticketPdfPath, "Ticket_{$booking->reference}.pdf", $caption, 'application/pdf');
        }

        return self::sendText($phone, $caption);
    }

    /**
     * 3. Send Verification / Login / Registration OTP
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
     * 4. Send Security / Intrusion Alert to Administrators
     */
    public static function sendAdminAlert(string $alertTitle, string $alertDetails): bool
    {
        if ((string) self::getSetting('whatsapp_notify_admin_security', '0') !== '1') {
            return false;
        }

        $rawNumbers = (string) self::getSetting('whatsapp_admin_numbers', '');
        if (empty($rawNumbers)) {
            return false;
        }

        $numbers = array_filter(array_map('trim', explode(',', $rawNumbers)));
        $message = "🚨 *PMCC-UK System Alert*\n\n" .
            "⚠️ *{$alertTitle}*\n\n" .
            "{$alertDetails}\n\n" .
            "🕒 " . date('d M Y H:i:s');

        $sentCount = 0;
        foreach ($numbers as $num) {
            if (self::sendText($num, $message)) {
                $sentCount++;
            }
        }

        return $sentCount > 0;
    }
}
