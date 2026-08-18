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

            $output = "[%datetime%] [IP: %extra.ip%] %channel%.%level_name%: %message% %context%\n";
            $formatter = new LineFormatter($output, 'Y-m-d H:i:s', true, true);
            $formatter->includeStacktraces(true);
            $handler->setFormatter($formatter);
        }
    }
}
