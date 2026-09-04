<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MemberApiController;

/*
|--------------------------------------------------------------------------
| PMCC-UK Member Mobile Application API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('/auth/login', [MemberApiController::class, 'login']);

    // Member Endpoints (Authenticated via Bearer Token)
    Route::get('/member/profile', [MemberApiController::class, 'profile']);
    Route::get('/member/card', [MemberApiController::class, 'digitalCard']);
    Route::get('/member/tickets', [MemberApiController::class, 'tickets']);
    Route::get('/member/transactions', [MemberApiController::class, 'transactions']);

    // Public & Community Endpoints
    Route::get('/community/events', [MemberApiController::class, 'events']);
    Route::get('/community/offers', [MemberApiController::class, 'offers']);
});
