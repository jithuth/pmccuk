<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

$count = 0;
// We use DB::table to bypass the 'encrypted' cast while reading, 
// then use Crypt::decrypt to get the value, 
// then update it back as plain text.

$members = DB::table('members')->get();

foreach ($members as $m) {
    if (empty($m->membership_id_assigned)) continue;

    try {
        // Try to decrypt. If it fails, it might already be plain text.
        $decrypted = Crypt::decryptString($m->membership_id_assigned);
        
        DB::table('members')->where('id', $m->id)->update([
            'membership_id_assigned' => $decrypted
        ]);
        echo "Decrypted ID for {$m->id}: {$decrypted}\n";
        $count++;
    } catch (\Exception $e) {
        echo "Skipped ID for {$m->id} (maybe already plain?): " . $e->getMessage() . "\n";
    }
}

echo "\nFinished! Decrypted $count IDs.\n";
