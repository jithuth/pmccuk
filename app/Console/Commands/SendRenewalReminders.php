<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Services\OpenWaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendRenewalReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pmcc:send-renewal-reminders {--dry-run : Run simulation without sending messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automated WhatsApp renewal reminder notices to members expiring within 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info("Scanning active members with memberships expiring in the next 30 days...");

        $today = Carbon::today()->format('Y-m-d');
        $thirtyDaysAhead = Carbon::today()->addDays(30)->format('Y-m-d');

        // Query active members whose expiry_date is between today and next 30 days
        $expiringMembers = Member::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $thirtyDaysAhead])
            ->get();

        if ($expiringMembers->isEmpty()) {
            $this->info("No members are currently due for renewal within the next 30 days.");
            return Command::SUCCESS;
        }

        $this->info("Found {$expiringMembers->count()} member(s) eligible for renewal notice.");

        $sentCount = 0;
        $failedCount = 0;

        foreach ($expiringMembers as $member) {
            if (empty($member->mobile_number)) {
                $this->warn("Skipping {$member->full_name} (No mobile phone recorded).");
                continue;
            }

            if ($isDryRun) {
                $this->line("[DRY-RUN] Would send renewal reminder to {$member->full_name} ({$member->mobile_number}), Expiry: {$member->expiry_date}");
                $sentCount++;
                continue;
            }

            $success = OpenWaService::notifyRenewalReminder($member);
            if ($success) {
                $this->info("Sent renewal reminder to {$member->full_name} ({$member->mobile_number}).");
                $sentCount++;
                // 1.5s rate-limit interval
                sleep(2);
            } else {
                $this->error("Failed to send renewal reminder to {$member->full_name}.");
                $failedCount++;
            }
        }

        $this->info("Done! Dispatched: {$sentCount}, Failed: {$failedCount}.");
        Log::info("[WhatsApp Cron] Renewal reminders completed. Dispatched: {$sentCount}, Failed: {$failedCount}.");

        return Command::SUCCESS;
    }
}
