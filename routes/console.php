<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pmcc:member-status', function () {
    $total = \App\Models\Member::count();
    $withPassword = \App\Models\Member::whereNotNull('password')->where('password', '!=', '')->count();
    $this->info("Total Members: {$total}");
    $this->info("Members with Password set: {$withPassword}");
    $this->info("Members using OTP authentication: " . ($total - $withPassword));
})->purpose('Inspect member authentication status');

Artisan::command('pmcc:set-member-password {identifier} {password}', function ($identifier, $password) {
    $member = \App\Models\Member::where('membership_id_assigned', $identifier)
        ->orWhere('id', $identifier)
        ->orWhere('email', $identifier)
        ->orWhere('mobile_number', $identifier)
        ->first();

    if (!$member) {
        $this->error("No member found matching: {$identifier}");
        return 1;
    }

    $member->password = \Illuminate\Support\Facades\Hash::make($password);
    $member->save();

    $this->info("Password successfully set for: {$member->full_name} ({$member->membership_id_assigned})");
    return 0;
})->purpose('Set or reset a password for a member');

