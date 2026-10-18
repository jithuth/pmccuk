<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class EncryptExistingMemberData extends Command
{
    protected $signature = 'app:encrypt-database {--dry-run : Run in simulation mode without updating database}';
    protected $description = 'Safely encrypts all plaintext PII records across the database with 100% round-trip verification';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn("=== RUNNING IN DRY-RUN SIMULATION MODE (NO DATABASE CHANGES) ===\n");
        } else {
            $this->alert("=== STARTING FULL PRODUCTION DATABASE ENCRYPTION (VERIFIED ZERO DATA LOSS) ===");
        }

        // 1. Repair Row 286 membership_id_assigned if encrypted
        $this->repairMembershipIdIfEncrypted('members', 'membership_id_assigned', 286, $dryRun);

        // Configuration of PII tables and columns to encrypt
        $config = [
            'members' => [
                'full_name', 'email', 'mobile_number', 'photo', 'family_photo', 
                'dob', 'marital_status', 'spouse_name', 'spouse_mobile', 'spouse_dob', 
                'emergency_name', 'emergency_mobile', 'post_code', 'house_details',
                'residing_area', 'prev_membership_no', 'transaction_ref', 'payment_proof',
                'bank_account_holder', 'privacy_notes'
            ],
            'member_children' => [
                'child_name', 'dob', 'sex'
            ],
            'renewal_requests' => [
                'full_name', 'email', 'mobile_number', 'photo', 'family_photo', 
                'dob', 'marital_status', 'spouse_name', 'spouse_mobile', 'spouse_dob', 
                'emergency_name', 'emergency_mobile', 'post_code', 'house_details',
                'bank_account_holder', 'transaction_ref', 'payment_proof'
            ],
            'renewal_children' => [
                'child_name', 'dob', 'sex'
            ],
            'event_bookings' => [
                'full_name', 'email', 'phone'
            ],
            'privacy_requests' => [
                'requester_name', 'requester_email', 'requester_phone', 'details', 'decision_reasoning'
            ],
            'admins' => [
                'email'
            ],
            'messages' => [
                'name', 'email', 'subject', 'message'
            ],
            'student_requests' => [
                'full_name', 'email', 'phone', 'university', 'study_year'
            ],
            'payment_history' => [
                'transaction_ref'
            ],
            'financial_transactions' => [
                'description', 'ref_no'
            ],
        ];

        $totalEncryptedAcrossAll = 0;

        foreach ($config as $table => $fields) {
            if (!Schema::hasTable($table)) {
                $this->line("Skipping table [{$table}] (not found).");
                continue;
            }

            $this->info("Processing table: [{$table}]...");
            $existingColumns = Schema::getColumnListing($table);
            $validFields = array_intersect($fields, $existingColumns);

            $rows = DB::table($table)->get();
            $tableCount = 0;
            $tableFieldsCount = 0;

            foreach ($rows as $row) {
                $updates = [];

                foreach ($validFields as $f) {
                    $raw = $row->$f ?? null;
                    if ($raw === null || $raw === '') {
                        continue;
                    }

                    if (!$this->isEncrypted($raw)) {
                        // Strict round-trip verification before saving
                        $encrypted = Crypt::encryptString((string)$raw);
                        $decrypted = Crypt::decryptString($encrypted);

                        if ($decrypted !== (string)$raw) {
                            $this->error("FATAL: Round-trip mismatch on table {$table}, row {$row->id}, column {$f}!");
                            $this->error("Original: '{$raw}' !== Decrypted: '{$decrypted}'");
                            return 1;
                        }

                        $updates[$f] = $encrypted;
                        $tableFieldsCount++;
                    }
                }

                if (!empty($updates)) {
                    if (!$dryRun) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                    $tableCount++;
                }
            }

            $action = $dryRun ? "Would update" : "Successfully encrypted";
            $this->line("  ✓ Table [{$table}]: {$action} {$tableCount} rows ({$tableFieldsCount} values encrypted).");
            $totalEncryptedAcrossAll += $tableFieldsCount;
        }

        $this->info("\n=======================================================");
        if ($dryRun) {
            $this->info("Dry-run complete: {$totalEncryptedAcrossAll} plaintext values verified ready for encryption.");
        } else {
            $this->info("Global database encryption completed successfully! Total values encrypted: {$totalEncryptedAcrossAll}");
        }
        $this->info("=======================================================\n");

        return 0;
    }

    private function repairMembershipIdIfEncrypted(string $table, string $column, int $id, bool $dryRun): void
    {
        if (!Schema::hasTable($table)) return;

        $row = DB::table($table)->where('id', $id)->first();
        if ($row && !empty($row->$column) && str_starts_with($row->$column, 'eyJ')) {
            try {
                $plain = Crypt::decryptString($row->$column);
                $this->warn("Found encrypted {$column} on {$table} ID {$id}. Decrypted value: '{$plain}'. Restoring to plaintext for query compatibility.");
                if (!$dryRun) {
                    DB::table($table)->where('id', $id)->update([$column => $plain]);
                }
            } catch (\Exception $e) {
                $this->error("Failed to decrypt {$column} on ID {$id}: " . $e->getMessage());
            }
        }
    }

    private function isEncrypted($value): bool
    {
        if (empty($value)) return true;
        if (!is_string($value) || !str_starts_with($value, 'eyJ')) return false;

        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
