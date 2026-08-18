<?php

namespace App\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Monolog\Level;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Cache;

class TelegramLogHandler extends AbstractProcessingHandler
{
    /**
     * Recursion prevention flag.
     */
    private static bool $isSending = false;

    public function __construct($level = Level::Warning, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    /**
     * Writes the record down to the log of the implementing handler
     */
    protected function write(LogRecord $record): void
    {
        if (self::$isSending) {
            return;
        }

        self::$isSending = true;

        try {
            $message = $record->message;
            $level = $record->level;
            $contextStr = !empty($record->context) ? json_encode($record->context, JSON_UNESCAPED_SLASHES) : '';
            $details = $message . ($contextStr ? "\nContext: " . $contextStr : '');
            $ip = $record->extra['ip'] ?? (request()?->ip() ?? 'CLI/System');

            // Cache key throttling for duplicate errors within 60 seconds
            $cacheKey = 'telegram_log_alert_' . md5($level->name . ':' . substr($message, 0, 100));
            if (Cache::has($cacheKey)) {
                self::$isSending = false;
                return;
            }
            Cache::put($cacheKey, true, 60);

            if ($level->value >= Level::Error->value) {
                TelegramService::sendErrorAlert("Application Error ({$level->name})", $details, $ip);
            } elseif ($level->value === Level::Warning->value) {
                TelegramService::sendWarningAlert("Application Warning", $details, $ip);
            }
        } catch (\Throwable $e) {
            // Ignore failure to prevent breaking application flow
        } finally {
            self::$isSending = false;
        }
    }
}
