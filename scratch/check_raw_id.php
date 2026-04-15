<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('members')->limit(5)->get();
foreach ($rows as $row) {
    echo "DB Raw ID: " . ($row->membership_id_assigned ?? 'NULL') . "\n";
}
