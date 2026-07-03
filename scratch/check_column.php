<?php
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$table = 'event_bookings';
$column = 'booking_status';

try {
    $results = DB::select("SELECT DISTINCT $column FROM $table");
    print_r($results);
    
    $cols = DB::select("SHOW COLUMNS FROM $table LIKE '$column'");
    print_r($cols);
} catch (\Exception $e) {
    echo $e->getMessage();
}
