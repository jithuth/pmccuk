<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventBooking;
use App\Services\OpenWaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pmcc:send-event-reminders {--dry-run : Run simulation without sending messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send 24-hour event reminder briefings to confirmed attendees';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $this->info("Scanning for PMCC events scheduled tomorrow ({$tomorrow})...");

        $events = Event::whereDate('event_date', $tomorrow)->get();

        if ($events->isEmpty()) {
            $this->info("No events scheduled for tomorrow ({$tomorrow}).");
            return Command::SUCCESS;
        }

        $totalSent = 0;
        $totalFailed = 0;

        foreach ($events as $event) {
            $this->info("Processing event: {$event->title} (ID: {$event->id})");

            $approvedBookings = EventBooking::where('event_id', $event->id)
                ->where('booking_status', 'approved')
                ->get();

            $this->line("Found {$approvedBookings->count()} approved booking(s).");

            foreach ($approvedBookings as $booking) {
                if (empty($booking->phone)) {
                    $this->warn("Skipping booking #{$booking->id} ({$booking->full_name}): No phone number.");
                    continue;
                }

                if ($isDryRun) {
                    $this->line("[DRY-RUN] Would send 24h event reminder to {$booking->full_name} ({$booking->phone}) for event '{$event->title}'.");
                    $totalSent++;
                    continue;
                }

                $success = OpenWaService::notifyEventReminder($booking);
                if ($success) {
                    $this->info("Sent reminder to {$booking->full_name} ({$booking->phone}).");
                    $totalSent++;
                    sleep(2); // Safe rate-limiting
                } else {
                    $this->error("Failed to send reminder to {$booking->full_name}.");
                    $totalFailed++;
                }
            }
        }

        $this->info("Done! Dispatched: {$totalSent}, Failed: {$totalFailed}.");
        Log::info("[WhatsApp Cron] Event reminders completed. Dispatched: {$totalSent}, Failed: {$totalFailed}.");

        return Command::SUCCESS;
    }
}
