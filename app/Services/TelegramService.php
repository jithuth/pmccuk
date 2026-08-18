<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class TelegramService
{
    /**
     * Send a notification message to the configured Telegram Chat(s).
     */
    public static function sendMessage(string $message, ?array $inlineKeyboard = null): bool
    {
        $token = self::getToken();
        $chatIds = self::getChatIds();

        if (empty($token) || empty($chatIds)) {
            Log::warning('Telegram Notification Skipped: BOT_TOKEN or CHAT_ID is not configured.');
            return false;
        }

        $payload = [
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($inlineKeyboard) {
            $payload['reply_markup'] = json_encode(['inline_keyboard' => $inlineKeyboard]);
        }

        $allSuccessful = true;
        foreach ($chatIds as $id) {
            try {
                $url = "https://api.telegram.org/bot{$token}/sendMessage";
                $payload['chat_id'] = $id;

                $response = Http::timeout(5)->post($url, $payload);

                if (!$response->successful()) {
                    Log::error("Telegram API Error for chat ID {$id}: " . $response->body());
                    $allSuccessful = false;
                }
            } catch (\Exception $e) {
                Log::error("Telegram Notification Failed for chat ID {$id}: " . $e->getMessage());
                $allSuccessful = false;
            }
        }

        return $allSuccessful;
    }

    /**
     * Edit an existing Telegram message (e.g. after approval button click).
     */
    public static function editMessageText(string $chatId, int $messageId, string $newText, ?array $inlineKeyboard = null): bool
    {
        $token = self::getToken();
        if (empty($token)) return false;

        try {
            $url = "https://api.telegram.org/bot{$token}/editMessageText";
            $payload = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $newText,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];

            if ($inlineKeyboard !== null) {
                $payload['reply_markup'] = json_encode(['inline_keyboard' => $inlineKeyboard]);
            }

            $response = Http::timeout(5)->post($url, $payload);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Telegram editMessageText Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Answer Telegram callback query (shows notification banner/popup on Telegram user screen).
     */
    public static function answerCallbackQuery(string $callbackQueryId, string $text, bool $showAlert = false): bool
    {
        $token = self::getToken();
        if (empty($token)) return false;

        try {
            $url = "https://api.telegram.org/bot{$token}/answerCallbackQuery";
            $response = Http::timeout(5)->post($url, [
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
                'show_alert' => $showAlert,
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Telegram answerCallbackQuery Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send Error Alert to Telegram.
     */
    public static function sendErrorAlert(string $title, string $details, ?string $ip = null): bool
    {
        $ipStr = $ip ?? (request()?->ip() ?? 'CLI/System');
        $timeStr = date('Y-m-d H:i:s');

        $msg = "🚨 <b>[SYSTEM ERROR ALERT]</b>\n\n" .
            "📅 <b>Time:</b> {$timeStr}\n" .
            "🌐 <b>IP Location:</b> {$ipStr}\n" .
            "🔥 <b>Issue:</b> " . htmlspecialchars($title) . "\n\n" .
            "📝 <b>Details:</b> <code>" . htmlspecialchars(substr($details, 0, 500)) . "</code>";

        return self::sendMessage($msg);
    }

    /**
     * Send Warning Alert to Telegram.
     */
    public static function sendWarningAlert(string $title, string $details, ?string $ip = null): bool
    {
        $ipStr = $ip ?? (request()?->ip() ?? 'CLI/System');
        $timeStr = date('Y-m-d H:i:s');

        $msg = "⚠️ <b>[SYSTEM WARNING ALERT]</b>\n\n" .
            "📅 <b>Time:</b> {$timeStr}\n" .
            "🌐 <b>IP Location:</b> {$ipStr}\n" .
            "⚠️ <b>Warning:</b> " . htmlspecialchars($title) . "\n\n" .
            "📝 <b>Details:</b> <code>" . htmlspecialchars(substr($details, 0, 500)) . "</code>";

        return self::sendMessage($msg);
    }

    /**
     * Send Success Alert to Telegram.
     */
    public static function sendSuccessAlert(string $title, string $details, ?string $ip = null): bool
    {
        $ipStr = $ip ?? (request()?->ip() ?? 'CLI/System');
        $timeStr = date('Y-m-d H:i:s');

        $msg = "✅ <b>[SYSTEM SUCCESS ALERT]</b>\n\n" .
            "📅 <b>Time:</b> {$timeStr}\n" .
            "🌐 <b>IP Location:</b> {$ipStr}\n" .
            "🎉 <b>Action:</b> " . htmlspecialchars($title) . "\n\n" .
            "📝 <b>Details:</b> " . htmlspecialchars(substr($details, 0, 500));

        return self::sendMessage($msg);
    }

    /**
     * Get configured Bot Token.
     */
    public static function getToken(): ?string
    {
        try {
            $tokenSetting = Setting::where('setting_key', 'telegram_bot_token')->first();
            return $tokenSetting ? $tokenSetting->setting_value : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get configured Chat IDs as array.
     */
    public static function getChatIds(): array
    {
        try {
            $chatIdSetting = Setting::where('setting_key', 'telegram_chat_id')->first();
            if (!$chatIdSetting || empty($chatIdSetting->setting_value)) {
                return [];
            }

            $chatIds = preg_split('/[\s,;]+/', trim($chatIdSetting->setting_value));
            return array_filter(array_map('trim', $chatIds));
        } catch (\Exception $e) {
            return [];
        }
    }
}
