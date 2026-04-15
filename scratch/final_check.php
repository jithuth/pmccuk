<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$id = "PMCC-104";
$member = App\Models\Member::where('membership_id_assigned', $id)->first();
if ($member) {
    echo "SUCCESS: Found member {$member->full_name} with ID {$id}\n";
} else {
    echo "FAILURE: Still cannot find ID {$id}\n";
}
