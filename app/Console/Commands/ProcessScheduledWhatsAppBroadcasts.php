<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsAppScheduledBroadcast;
use App\Models\Member;
use App\Models\EventBooking;
use App\Models\StudentRequest;
use App\Services\OpenWaService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessScheduledWhatsAppBroadcasts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:process-scheduled {--dry-run : Only check and display pending broadcasts without dispatching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and dispatch due scheduled WhatsApp broadcasts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $dueBroadcasts = WhatsAppScheduledBroadcast::due()
            ->orderBy('scheduled_at', 'asc')
            ->get();

        if ($dueBroadcasts->isEmpty()) {
            $this->line('No scheduled WhatsApp broadcasts are due for processing.');
            return 0;
        }

        $this->info("Found {$dueBroadcasts->count()} due broadcast(s).");

        foreach ($dueBroadcasts as $broadcast) {
            $this->line("Processing broadcast #{$broadcast->id} ({$broadcast->audience})...");

            if ($isDryRun) {
                $this->comment("[DRY RUN] Would execute broadcast #{$broadcast->id} scheduled for " . ($broadcast->scheduled_at ?? 'Immediately'));
                continue;
            }

            // Mark as processing
            $broadcast->update([
                'status' => 'processing',
                'executed_at' => Carbon::now()
            ]);

            $recipients = $this->resolveRecipients($broadcast);
            $total = count($recipients);
            $broadcast->update(['total_recipients' => $total]);

            if ($total === 0) {
                $broadcast->update([
                    'status' => 'failed',
                    'error_log' => 'No valid recipients could be resolved for target audience: ' . $broadcast->audience
                ]);
                $this->warn("No recipients found for broadcast #{$broadcast->id}");
                continue;
            }

            $sent = 0;
            $failed = 0;
            $logs = [];

            foreach ($recipients as $idx => $r) {
                $phone = $r['phone'] ?? null;
                $name = $r['name'] ?? 'Community Member';

                if (!$phone) {
                    $failed++;
                    continue;
                }

                $ok = OpenWaService::dispatchBroadcastBundle(
                    $phone,
                    $name,
                    $broadcast->message,
                    $broadcast->attachments ?? []
                );

                if ($ok) {
                    $sent++;
                    $logs[] = "[SUCCESS] Dispatched to {$name} ({$phone})";
                } else {
                    $failed++;
                    $logs[] = "[FAILED] Delivery failed for {$name} ({$phone})";
                }

                // Batch pacing: 400ms delay between recipients
                usleep(400000);
            }

            $broadcast->update([
                'status' => $sent > 0 ? 'completed' : 'failed',
                'sent_count' => $sent,
                'failed_count' => $failed,
                'error_log' => implode("\n", array_slice($logs, 0, 100))
            ]);

            $this->info("Broadcast #{$broadcast->id} completed: {$sent} sent, {$failed} failed.");
            Log::info("[WhatsApp Broadcast] Scheduled broadcast #{$broadcast->id} finished: {$sent}/{$total} sent.");
        }

        return 0;
    }

    /**
     * Resolve recipients according to the audience segment
     */
    protected function resolveRecipients(WhatsAppScheduledBroadcast $broadcast): array
    {
        $recipients = [];
        $audience = $broadcast->audience;

        if ($audience === 'members') {
            $members = Member::where('status', 'active')
                ->whereNotNull('mobile_number')
                ->get();
            foreach ($members as $m) {
                $recipients[] = [
                    'phone' => $m->mobile_number,
                    'name' => $m->full_name
                ];
            }
        } elseif ($audience === 'attendees') {
            $bookings = EventBooking::where('booking_status', 'approved')
                ->whereNotNull('phone')
                ->get();
            foreach ($bookings as $b) {
                $recipients[] = [
                    'phone' => $b->phone,
                    'name' => $b->full_name
                ];
            }
        } elseif ($audience === 'students') {
            $students = StudentRequest::whereNotNull('phone')->get();
            foreach ($students as $s) {
                $recipients[] = [
                    'phone' => $s->phone,
                    'name' => $s->full_name ?? 'Student'
                ];
            }
        } elseif ($audience === 'executives') {
            $raw = OpenWaService::getSetting('whatsapp_favorite_executives', '[]');
            $list = json_decode($raw, true) ?: [];
            $selectedIds = $broadcast->selected_executives;

            foreach ($list as $item) {
                if (!empty($selectedIds) && is_array($selectedIds)) {
                    if (!in_array((string) ($item['id'] ?? ''), array_map('strval', $selectedIds))) {
                        continue;
                    }
                }
                if (!empty($item['phone'])) {
                    $recipients[] = [
                        'phone' => $item['phone'],
                        'name' => $item['name'] ?? 'Executive Member'
                    ];
                }
            }
        } elseif ($audience === 'custom') {
            $raw = $broadcast->custom_numbers ?? '';
            $lines = preg_split('/[\r\n,]+/', $raw);
            foreach ($lines as $line) {
                $num = trim($line);
                if (!empty($num)) {
                    $recipients[] = [
                        'phone' => $num,
                        'name' => 'Community Member'
                    ];
                }
            }
        }

        // De-duplicate by normalized phone number
        $unique = [];
        $result = [];
        foreach ($recipients as $r) {
            $clean = OpenWaService::formatPhone($r['phone']);
            if ($clean && !isset($unique[$clean])) {
                $unique[$clean] = true;
                $result[] = $r;
            }
        }

        return $result;
    }
}
