<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$members = App\Models\Member::select('membership_id_assigned', 'status', 'full_name')->take(20)->get();
foreach ($members as $m) {
    echo "ID: {$m->membership_id_assigned} | Status: {$m->status} | Name: {$m->full_name}\n";
}
