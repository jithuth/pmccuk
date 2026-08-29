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
     * Send a message to a specific Chat ID (e.g. direct response to a user in Telegram).
     */
    public static function sendMessageToChat(string $chatId, string $message, ?array $inlineKeyboard = null): bool
    {
        $token = self::getToken();
        if (empty($token)) return false;

        try {
            $url = "https://api.telegram.org/bot{$token}/sendMessage";
            $payload = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];

            if ($inlineKeyboard) {
                $payload['reply_markup'] = json_encode(['inline_keyboard' => $inlineKeyboard]);
            }

            $response = Http::timeout(5)->post($url, $payload);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Telegram sendMessageToChat Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a document/file attachment (e.g. PDF ID Card) to a specific Chat ID via Telegram Bot API.
     */
    public static function sendDocument(string $chatId, string $filePath, string $filename, string $caption = ''): bool
    {
        $token = self::getToken();
        if (empty($token) || !file_exists($filePath)) {
            Log::warning("Telegram sendDocument failed: Token missing or file not found at {$filePath}");
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$token}/sendDocument";
            $response = Http::timeout(20)
                ->attach('document', file_get_contents($filePath), $filename)
                ->post($url, [
                    'chat_id' => $chatId,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);

            if (!$response->successful()) {
                Log::error("Telegram sendDocument API Error: " . $response->body());
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Telegram sendDocument Exception: " . $e->getMessage());
            return false;
        }
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
     * Send a photo to configured Telegram chat(s).
     */
    public static function sendPhoto(string $photoPath, string $caption = ''): bool
    {
        $token = self::getToken();
        $chatIds = self::getChatIds();

        if (empty($token) || empty($chatIds) || !file_exists($photoPath)) {
            Log::warning("Telegram sendPhoto skipped: Token/ChatID missing or photo file not found at {$photoPath}");
            return false;
        }

        $allSuccessful = true;
        foreach ($chatIds as $id) {
            try {
                $url = "https://api.telegram.org/bot{$token}/sendPhoto";
                $response = Http::timeout(10)
                    ->attach('photo', file_get_contents($photoPath), 'snapshot.jpg')
                    ->post($url, [
                        'chat_id' => $id,
                        'caption' => $caption,
                        'parse_mode' => 'HTML',
                    ]);

                if (!$response->successful()) {
                    Log::error("Telegram sendPhoto API Error for chat ID {$id}: " . $response->body());
                    $allSuccessful = false;
                }
            } catch (\Exception $e) {
                Log::error("Telegram sendPhoto Exception for chat ID {$id}: " . $e->getMessage());
                $allSuccessful = false;
            }
        }

        return $allSuccessful;
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

    /**
     * Register slash command autocompletion menu with Telegram API.
     */
    public static function setBotCommands(): bool
    {
        $token = self::getToken();
        if (empty($token)) return false;

        $url = "https://api.telegram.org/bot{$token}/setMyCommands";
        $commands = [
            ['command' => 'menu', 'description' => 'Open Interactive Admin Control Panel'],
            ['command' => 'help', 'description' => 'Show all available commands & manual'],
            ['command' => 'logins', 'description' => 'View Recent Logins (15 Records)'],
            ['command' => 'logs', 'description' => 'View Recent Activity Logs (10 Records)'],
            ['command' => 'stats', 'description' => 'Live Dashboard & Revenue Summary'],
            ['command' => 'security', 'description' => 'Web Application Firewall & Security Report'],
            ['command' => 'passcode', 'description' => 'Generate Emergency 2FA Code'],
            ['command' => 'server', 'description' => 'View Server Disk, RAM & System Health'],
            ['command' => 'backup', 'description' => 'Download Full SQL Database Backup'],
            ['command' => 'income', 'description' => 'Quick-Log Income (/income amount description)'],
            ['command' => 'expense', 'description' => 'Quick-Log Expense (/expense amount description)'],
        ];

        try {
            $response = Http::timeout(5)->post($url, ['commands' => json_encode($commands)]);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
