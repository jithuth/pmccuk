<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class TelegramService
{
    /**
     * Send a notification message to the configured Telegram Chat.
     */
    public static function sendMessage(string $message): bool
    {
        $token = null;
        $chatId = null;

        try {
            // Load dynamically from database to support decryption casts
            $tokenSetting = Setting::where('setting_key', 'telegram_bot_token')->first();
            if ($tokenSetting) {
                $token = $tokenSetting->setting_value;
            }

            $chatIdSetting = Setting::where('setting_key', 'telegram_chat_id')->first();
            if ($chatIdSetting) {
                $chatId = $chatIdSetting->setting_value;
            }
        } catch (\Exception $e) {
            Log::error('Telegram Config Error: ' . $e->getMessage());
        }

        if (empty($token) || empty($chatId)) {
            Log::warning('Telegram Notification Skipped: BOT_TOKEN or CHAT_ID is not configured.');
            return false;
        }

        // Split multiple chat IDs (by spaces, commas, semicolons, or newlines)
        $chatIds = preg_split('/[\s,;]+/', trim($chatId));
        $chatIds = array_filter(array_map('trim', $chatIds));

        if (empty($chatIds)) {
            Log::warning('Telegram Notification Skipped: No valid CHAT_IDs found.');
            return false;
        }

        $allSuccessful = true;
        foreach ($chatIds as $id) {
            try {
                $url = "https://api.telegram.org/bot{$token}/sendMessage";
                $response = Http::timeout(5)->post($url, [
                    'chat_id' => $id,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]);

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
}
