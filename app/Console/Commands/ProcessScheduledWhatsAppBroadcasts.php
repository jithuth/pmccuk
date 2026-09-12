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

            // Resolve recipients if not pre-populated
            $recipients = $broadcast->recipients_data ?: [];
            if (empty($recipients)) {
                $rawResolved = $this->resolveRecipients($broadcast);
                foreach ($rawResolved as $idx => $r) {
                    $recipients[] = [
                        'id' => $idx + 1,
                        'phone' => $r['phone'],
                        'name' => $r['name'],
                        'status' => 'pending',
                        'sent_at' => null,
                        'error' => null
                    ];
                }
                $broadcast->recipients_data = $recipients;
                $broadcast->total_recipients = count($recipients);
                $broadcast->save();
            }

            $total = count($recipients);
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

            // Calculate existing counts
            foreach ($recipients as $item) {
                if (($item['status'] ?? '') === 'sent') $sent++;
                elseif (($item['status'] ?? '') === 'failed') $failed++;
            }

            $batchLimit = 20; // 20 recipients per batch
            $processedInCurrentBatch = 0;

            foreach ($recipients as $idx => $r) {
                // If already sent, skip
                if (($r['status'] ?? '') === 'sent') {
                    continue;
                }

                // If this batch of 20 is complete, pause for 3-minute gap
                if ($processedInCurrentBatch >= $batchLimit) {
                    break;
                }

                $phone = $r['phone'] ?? null;
                $name = $r['name'] ?? 'Community Member';

                if (!$phone) {
                    $recipients[$idx]['status'] = 'failed';
                    $recipients[$idx]['error'] = 'Missing phone number';
                    $failed++;
                    $processedInCurrentBatch++;
                    continue;
                }

                $res = OpenWaService::dispatchBroadcastBundle(
                    $phone,
                    $name,
                    $broadcast->message,
                    $broadcast->attachments ?? []
                );

                $isOk = is_array($res) ? ($res['success'] ?? false) : (bool)$res;
                $msgId = is_array($res) ? ($res['message_id'] ?? null) : null;

                if ($isOk) {
                    $sent++;
                    $recipients[$idx]['status'] = 'sent';
                    $recipients[$idx]['message_id'] = $msgId;
                    $recipients[$idx]['sent_at'] = Carbon::now()->toDateTimeString();
                    $recipients[$idx]['error'] = null;
                    $logs[] = "[SUCCESS " . date('H:i:s') . "] Dispatched to {$name} (" . OpenWaService::formatPhoneDisplay($phone) . ")";
                } else {
                    $failed++;
                    $recipients[$idx]['status'] = 'failed';
                    $recipients[$idx]['message_id'] = null;
                    $recipients[$idx]['sent_at'] = Carbon::now()->toDateTimeString();
                    $recipients[$idx]['error'] = is_array($res) ? ($res['error'] ?? 'Gateway delivery failed') : 'Gateway delivery failed';
                    $logs[] = "[FAILED " . date('H:i:s') . "] Delivery failed for {$name} (" . OpenWaService::formatPhoneDisplay($phone) . ")";
                }

                $processedInCurrentBatch++;

                // Pacing: 400ms delay between individual recipients in the batch
                usleep(400000);

                // Save checkpoint every 5 dispatches for durability
                if ($idx % 5 === 0) {
                    $broadcast->recipients_data = $recipients;
                    $broadcast->sent_count = $sent;
                    $broadcast->failed_count = $failed;
                    $broadcast->save();
                }
            }

            $remaining = 0;
            foreach ($recipients as $item) {
                if (($item['status'] ?? 'pending') === 'pending') $remaining++;
            }

            if ($remaining > 0) {
                // 3-Minute anti-spam cooldown before next batch of 20
                $nextBatchAt = Carbon::now()->addMinutes(3);
                $broadcast->update([
                    'status' => 'processing',
                    'scheduled_at' => $nextBatchAt,
                    'sent_count' => $sent,
                    'failed_count' => $failed,
                    'recipients_data' => $recipients,
                    'error_log' => implode("\n", array_slice($logs, -100))
                ]);
                $this->info("Broadcast #{$broadcast->id}: Batch of {$processedInCurrentBatch} processed. Next batch of 20 scheduled in 3 minutes at {$nextBatchAt->format('H:i:s')}. ({$remaining} remaining)");
                Log::info("[WhatsApp Broadcast] Broadcast #{$broadcast->id}: batch of {$processedInCurrentBatch} sent. Next batch in 3 mins ({$remaining} remaining).");
            } else {
                $broadcast->update([
                    'status' => ($sent > 0 ? 'completed' : 'failed'),
                    'sent_count' => $sent,
                    'failed_count' => $failed,
                    'recipients_data' => $recipients,
                    'error_log' => implode("\n", array_slice($logs, -100))
                ]);
                $this->info("Broadcast #{$broadcast->id} fully completed: {$sent} sent, {$failed} failed.");
                Log::info("[WhatsApp Broadcast] Scheduled broadcast #{$broadcast->id} finished: {$sent}/{$total} sent.");
            }
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
