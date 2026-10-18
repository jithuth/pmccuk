<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class AuditDatabaseEncryption extends Command
{
    protected $signature = 'app:audit-encryption {--schema}';
    protected $description = 'Inspect table schema and column types for encryption readiness';

    public function handle()
    {
        $tables = ['members', 'member_children', 'renewal_requests', 'renewal_children', 'event_bookings', 'privacy_requests'];

        foreach ($tables as $t) {
            $this->info("Table: {$t}");
            $cols = DB::select("SHOW COLUMNS FROM {$t}");
            foreach ($cols as $c) {
                $isText = str_contains(strtolower($c->Type), 'text') || str_contains(strtolower($c->Type), 'varchar(255)');
                $color = $isText ? 'green' : 'yellow';
                $this->line("  {$c->Field}: <fg={$color}>{$c->Type}</>");
            }
            $this->line("");
        }

        // Also check row 286 membership_id_assigned
        $row286 = DB::table('members')->where('id', 286)->first();
        if ($row286) {
            $val = $row286->membership_id_assigned;
            if (str_starts_with($val, 'eyJ')) {
                try {
                    $dec = Crypt::decryptString($val);
                    $this->info("Row 286 membership_id_assigned decrypts to: '{$dec}'");
                } catch (\Exception $e) {
                    $this->error("Row 286 could not decrypt: " . $e->getMessage());
                }
            } else {
                $this->info("Row 286 membership_id_assigned is: '{$val}'");
            }
        }

        return 0;
    }
}
