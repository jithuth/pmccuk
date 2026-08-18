<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class AddAuditInfo
{
    /**
     * Customize the given logger instance.
     */
    public function __invoke(Logger $logger): void
    {
        // Push TelegramLogHandler to logger to catch Error & Warning logs
        $logger->pushHandler(new TelegramLogHandler());

        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(function (LogRecord $record) {
                $ip = 'CLI';

                try {
                    if (app()->runningInConsole()) {
                        $ip = 'CLI';
                    } elseif ($request = request()) {
                        $ip = $request->ip() ?? 'UNKNOWN';
                    }
                } catch (\Throwable $e) {
                    $ip = 'UNKNOWN';
                }

                return $record->with(extra: array_merge($record->extra, [
                    'ip' => $ip,
                ]));
            });

            // Set custom LineFormatter to include timestamp and IP location header
            $output = "[%datetime%] [IP: %extra.ip%] %channel%.%level_name%: %message% %context%\n";
            $formatter = new LineFormatter($output, 'Y-m-d H:i:s', true, true);
            $formatter->includeStacktraces(true);
            $handler->setFormatter($formatter);
        }
    }
}
