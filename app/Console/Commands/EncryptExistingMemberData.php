<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class EncryptExistingMemberData extends Command
{
    protected $signature = 'app:encrypt-database';
    protected $description = 'Encrypts ALL PII in the database that is currently in plaintext';

    public function handle()
    {
        $config = [
            'admins' => ['email'],
            'members' => [
                'full_name', 'email', 'mobile_number', 'photo', 'family_photo', 
                'dob', 'spouse_name', 'spouse_mobile', 'spouse_dob', 
                'emergency_name', 'emergency_mobile', 'post_code', 'house_details',
                'prev_membership_no', 'membership_id_assigned', 'transaction_ref', 'payment_proof', 'bank_account_holder'
            ],
            'member_children' => ['child_name', 'dob'],
            'renewal_requests' => [
                'full_name', 'email', 'mobile_number', 'photo', 'family_photo', 
                'dob', 'spouse_name', 'spouse_mobile', 'spouse_dob', 
                'emergency_name', 'emergency_mobile', 'post_code', 'house_details',
                'bank_account_holder', 'transaction_ref', 'payment_proof'
            ],
            'renewal_children' => ['child_name', 'dob'],
            'event_bookings' => ['full_name', 'email', 'phone'],
            'payment_history' => ['transaction_ref'],
            'financial_transactions' => ['description', 'ref_no'],
            'settings' => ['setting_value'],
            'menus' => ['title', 'url'],
            'team_members' => ['name', 'role'],
            'sponsor_offers' => ['sponsor_name', 'title', 'description'],
            'news' => ['title', 'content'],
            'activity_logs' => ['action', 'details'],
            'messages' => ['name', 'email', 'subject', 'message'],
            'student_requests' => ['full_name', 'email', 'phone', 'university', 'study_year'],
        ];

        foreach ($config as $table => $fields) {
            if (!Schema::hasTable($table)) continue;

            $this->info("Starting encryption for table: {$table}...");
            $rows = DB::table($table)->get();
            $count = 0;

            foreach ($rows as $row) {
                $updates = [];
                foreach ($fields as $f) {
                    if (isset($row->$f) && !empty($row->$f) && !$this->isEncrypted($row->$f)) {
                        $updates[$f] = Crypt::encryptString($row->$f);
                    }
                }
                if (!empty($updates)) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                    $count++;
                }
            }
            $this->info("Updated {$count} rows in {$table}.");
        }

        $this->info('Global encryption complete!');
    }

    private function isEncrypted($value)
    {
        if (empty($value)) return true; // Treat empty as "done"
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
