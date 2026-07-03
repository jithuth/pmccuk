<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$types = DB::select('SELECT DISTINCT membership_type FROM members');
foreach($types as $t) {
    echo "TYPE: " . $t->membership_type . "\n";
}
