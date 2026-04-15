<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$member = App\Models\Member::where('membership_id_assigned', 'LIKE', '%104%')->first();
if ($member) {
    echo "ID found: '" . $member->membership_id_assigned . "'\n";
    echo "Hex: " . bin2hex($member->membership_id_assigned) . "\n";
    echo "Status: " . $member->status . "\n";
} else {
    echo "No member found with 104 in ID\n";
}
