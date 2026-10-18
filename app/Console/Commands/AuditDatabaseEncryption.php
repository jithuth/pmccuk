<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditDatabaseEncryption extends Command
{
    protected $signature = 'app:audit-encryption';
    protected $description = 'Scan database tables and report plaintext vs encrypted fields';

    public function handle()
    {
        $tables = [
            'members' => [
                'full_name', 'email', 'mobile_number', 'photo', 'family_photo',
                'dob', 'marital_status', 'spouse_name', 'spouse_mobile', 'spouse_dob',
                'emergency_name', 'emergency_mobile', 'post_code', 'house_details',
                'residing_area', 'prev_membership_no', 'membership_id_assigned',
                'transaction_ref', 'payment_proof', 'bank_account_holder'
            ],
            'member_children' => ['child_name', 'dob', 'sex'],
            'renewal_requests' => [
                'full_name', 'email', 'mobile_number', 'photo', 'family_photo',
                'dob', 'marital_status', 'spouse_name', 'spouse_mobile', 'spouse_dob',
                'emergency_name', 'emergency_mobile', 'post_code', 'house_details',
                'bank_account_holder', 'transaction_ref', 'payment_proof'
            ],
            'renewal_children' => ['child_name', 'dob', 'sex'],
            'event_bookings' => ['full_name', 'email', 'phone', 'membership_no'],
            'payment_history' => ['transaction_ref'],
            'financial_transactions' => ['description', 'ref_no'],
            'messages' => ['name', 'email', 'subject', 'message'],
            'student_requests' => ['full_name', 'email', 'phone', 'university', 'study_year'],
            'privacy_requests' => ['requester_name', 'requester_email', 'requester_phone', 'details', 'decision_reasoning'],
        ];

        $this->info("Scanning database tables for post-encryption status...\n");

        foreach ($tables as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $count = DB::table($table)->count();
            $this->line("<fg=cyan;options=bold>Table: {$table}</> (Total rows: {$count})");

            if ($count === 0) {
                $this->line("  (No rows)\n");
                continue;
            }

            $existingColumns = Schema::getColumnListing($table);
            $rows = DB::table($table)->get();

            foreach ($columns as $column) {
                if (!in_array($column, $existingColumns)) {
                    continue;
                }

                $encrypted = 0;
                $plaintext = 0;
                $null = 0;
                $sample = [];

                foreach ($rows as $row) {
                    $val = $row->$column ?? null;
                    if ($val === null || $val === '') {
                        $null++;
                    } elseif (is_string($val) && str_starts_with($val, 'eyJ')) {
                        $encrypted++;
                    } else {
                        $plaintext++;
                        if (count($sample) < 2) {
                            $sample[] = strlen($val) > 20 ? substr($val, 0, 17) . '...' : $val;
                        }
                    }
                }

                if ($plaintext > 0 && $encrypted > 0) {
                    $this->warn("  - {$column}: {$plaintext} PLAINTEXT, {$encrypted} encrypted ({$null} null). Sample: " . implode(', ', $sample));
                } elseif ($plaintext > 0 && $encrypted === 0) {
                    $this->line("  - {$column}: Public Identifier / Plaintext ({$plaintext} values, {$null} null). Sample: " . implode(', ', $sample));
                } else {
                    $this->info("  ✓ {$column}: 100% ENCRYPTED ({$encrypted} encrypted, {$null} null)");
                }
            }
            $this->line("");
        }

        return 0;
    }
}
