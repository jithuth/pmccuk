<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Member;
use Illuminate\Support\Str;

$members = Member::whereNull('guid')->orWhere('guid', '')->get();
echo "Found " . $members->count() . " members without GUID.\n";

foreach($members as $m) {
    $m->guid = (string) Str::uuid();
    $m->save();
}

echo "Migration complete.\n";
