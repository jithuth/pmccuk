<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\EventController;

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\EventBookingController;

// 🚨 SYSTEM-LEVEL ASSET FAILSAFE (MUST BE TOP)
Route::get('/img', function() {
    $p = request('p');
    if (!$p) return "Missing 'p' parameter.";
    
    // 🛡️ Clean up path from double-prefixes
    $p = str_replace(['img?p=', 'storage/', '/storage/', 'uploads/'], '', $p);
    $p = ltrim($p, '/');

    $f = storage_path('app/public/' . $p);
    if (!file_exists($f)) $f = storage_path('app/public/cms/' . $p);
    if (!file_exists($f)) $f = storage_path('app/public/photos/' . $p);
    if (!file_exists($f)) $f = storage_path('app/public/uploads/' . $p);
    
    if (!file_exists($f) || is_dir($f)) return "File not found at: $f";
    return response()->file($f);
});

// 🟢 TOP PRIORITY: UNIVERSAL ASSET INTERCEPTOR (Bypassing server blocks)
Route::any('{any_prefix?}/{media_type}/{path}', function ($prefix, $type, $path) {
    if (!in_array($type, ['media', 'storage', 'uploads', 'cdn'])) return abort(404);
    $searchPaths = [
        storage_path('app/public/' . $path),
        storage_path('app/public/cms/' . $path),
        storage_path('app/public/uploads/' . $path),
        storage_path('app/public/photos/' . $path),
        storage_path($path), 
    ];
    foreach ($searchPaths as $fullPath) {
        if (file_exists($fullPath) && !is_dir($fullPath)) return response()->file($fullPath);
    }
    return abort(404);
})->where('any_prefix', 'public|.*')->where('media_type', 'media|storage|uploads|cdn')->where('path', '.*');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/path-debug', function() {
    $paths = [
        'public_path' => public_path(),
        'base_path' => base_path(),
        'storage_path' => storage_path(),
        'storage_app_public' => storage_path('app/public'),
        'image_test' => storage_path('app/public/cms/8Epo19is75LBHzjjdQJ7XbwmBP6XkEzs1ohQEuLa.jpg'),
        'image_exists' => file_exists(storage_path('app/public/cms/8Epo19is75LBHzjjdQJ7XbwmBP6XkEzs1ohQEuLa.jpg')) ? 'YES' : 'NO'
    ];
    return response()->json($paths);
});

Route::get('/fix-storage', function() {
    try {
        $path = public_path('storage');
        if (file_exists($path)) {
            if (is_link($path) || is_file($path)) unlink($path);
            else if (is_dir($path)) rename($path, $path . '_bak_' . time());
        }
        $target = storage_path('app/public');
        if (@symlink($target, $path)) return "SUCCESS: Link created!";
        return "FAILED: Symlink refused. Use /path-debug to see why.";
    } catch (\Exception $e) {
        return "ERROR: " . $e->getMessage();
    }
});

Route::get('/clear-everything', function() {
    try {
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        return "All caches cleared successfully! Now try <a href='/fix-storage'>/fix-storage</a>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/membership', [MembershipController::class, 'index'])->name('membership');
Route::post('/api/membership/send-otp', [MembershipController::class, 'sendOtp']);
Route::post('/api/membership/verify-otp', [MembershipController::class, 'verifyOtp']);
Route::post('/membership/submit', [MembershipController::class, 'submit'])->name('membership.submit')->middleware(\App\Http\Middleware\CheckHoneypot::class);

Route::get('/events', [EventController::class, 'index'])->name('events');
Route::get('/events/{id}', [EventController::class, 'show'])->name('events.show');

Route::get('/news', [NewsController::class, 'index'])->name('news');
Route::get('/news/{id}', [NewsController::class, 'show'])->name('news.show');

Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');
Route::get('/offers', [OfferController::class, 'index'])->name('offers');
Route::get('/team', [TeamController::class, 'index'])->name('team');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/student-corner', function () {
    return view('student_corner');
})->name('student-corner');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login')->middleware('throttle:login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // ── 2FA Challenge (mid-login, no auth guard needed yet) ──
    Route::get('/2fa/challenge', [\App\Http\Controllers\Admin\TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
    Route::post('/2fa/challenge', [\App\Http\Controllers\Admin\TwoFactorController::class, 'verifyChallenge'])->name('2fa.verify');

    Route::middleware('auth:admin')->group(function () {
        // ── 2FA Setup & Management ──
        Route::get('/2fa/setup', [\App\Http\Controllers\Admin\TwoFactorController::class, 'showSetup'])->name('2fa.setup');
        Route::post('/2fa/confirm', [\App\Http\Controllers\Admin\TwoFactorController::class, 'confirmSetup'])->name('2fa.confirm');
        Route::delete('/2fa/disable', [\App\Http\Controllers\Admin\TwoFactorController::class, 'disable'])->name('2fa.disable');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // Membership Hub
        Route::prefix('members')->name('members.')->group(function () {
            Route::get('/', [DashboardController::class, 'members'])->name('index');
            Route::get('/new', [DashboardController::class, 'newMembers'])->name('new');
            Route::get('/renewals', [DashboardController::class, 'renewals'])->name('renewals');
            Route::post('/renewals/{id}/approve', [DashboardController::class, 'approveRenewal'])->name('renewals.approve');
            Route::post('/{id}/approve', [DashboardController::class, 'approveMember'])->name('approve');
            Route::get('/{id}/details', [DashboardController::class, 'getMemberDetails'])->name('details');
            Route::post('/{id}/update', [DashboardController::class, 'updateMember'])->name('update');
            Route::delete('/{id}', [DashboardController::class, 'deleteMember'])->name('delete');
            Route::post('/{id}/restore', [DashboardController::class, 'restoreMember'])->name('restore');
            Route::post('/{id}/send-card-email', [DashboardController::class, 'sendCardEmail'])->name('send-card-email');
            Route::get('/{id?}/print-card', [DashboardController::class, 'printIdCard'])->name('print-card');
            Route::get('/verify/{id}/{token}', [DashboardController::class, 'verifyMembership'])->name('verify');
            Route::get('/import', [DashboardController::class, 'importMembers'])->name('import');
        });

        // CRM & Inquiries
        Route::get('/messages', [DashboardController::class, 'messages'])->name('messages');
        Route::patch('/messages/{id}/read', [DashboardController::class, 'markMessageRead'])->name('messages.read');
        Route::delete('/messages/{id}', [DashboardController::class, 'deleteMessage'])->name('messages.delete');
        Route::get('/student-requests', [DashboardController::class, 'studentRequests'])->name('student-requests');

        // Website & Content
        Route::get('/home-banners', [DashboardController::class, 'homeBanners'])->name('home-banners');
        Route::get('/about-content', [DashboardController::class, 'aboutContent'])->name('about-content');
        Route::get('/team', [DashboardController::class, 'team'])->name('team');
        Route::post('/team', [DashboardController::class, 'addTeamMember'])->name('team.add');
        Route::post('/team/{id}', [DashboardController::class, 'updateTeamMember'])->name('team.update');
        Route::delete('/team/{id}', [DashboardController::class, 'deleteTeamMember'])->name('team.delete');
        Route::get('/menus', [DashboardController::class, 'menus'])->name('menus');
        Route::post('/menus', [DashboardController::class, 'addMenu'])->name('menus.add');
        Route::post('/menus/{id}', [DashboardController::class, 'updateMenu'])->name('menus.update');
        Route::delete('/menus/{id}', [DashboardController::class, 'deleteMenu'])->name('menus.delete');
        Route::get('/news', [DashboardController::class, 'news'])->name('news');
        Route::post('/news', [DashboardController::class, 'addNews'])->name('news.add');
        Route::delete('/news/{id}', [DashboardController::class, 'deleteNews'])->name('news.delete');

        // Events & Pricing
        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [DashboardController::class, 'events'])->name('index');
            Route::post('/', [DashboardController::class, 'addEvent'])->name('add');
            Route::post('/{id}', [DashboardController::class, 'updateEvent'])->name('update');
            Route::delete('/{id}', [DashboardController::class, 'deleteEvent'])->name('delete');
            Route::get('/bookings', [EventBookingController::class, 'index'])->name('bookings');
            Route::get('/bookings/{id}/status/{status}', [EventBookingController::class, 'updateStatus'])->name('bookings.status');
            Route::get('/bookings/{id}/resend', [EventBookingController::class, 'resendTicket'])->name('bookings.resend');
            Route::get('/bookings/{id}/edit', [EventBookingController::class, 'edit'])->name('bookings.edit');
            Route::post('/bookings/{id}/update', [EventBookingController::class, 'update'])->name('bookings.update');
            Route::delete('/bookings/{id}', [EventBookingController::class, 'destroy'])->name('bookings.delete');
            Route::get('/fare-logic', [DashboardController::class, 'fareLogic'])->name('fare-logic');
            Route::get('/stats', [DashboardController::class, 'eventStats'])->name('stats');
        });

        // Sponsors & Offers
        Route::prefix('sponsors')->name('sponsors.')->group(function () {
            Route::get('/offers', [DashboardController::class, 'offers'])->name('offers');
            Route::post('/offers', [DashboardController::class, 'addOffer'])->name('offers.add');
            Route::post('/offers/{id}', [DashboardController::class, 'updateOffer'])->name('offers.update');
            Route::get('/redemptions', [DashboardController::class, 'redemptions'])->name('redemptions');
        });

        // Media & Financials
        Route::get('/gallery', [DashboardController::class, 'gallery'])->name('gallery');
        Route::post('/gallery', [DashboardController::class, 'addGallery'])->name('gallery.add');
        Route::delete('/gallery/{id}', [DashboardController::class, 'deleteGallery'])->name('gallery.delete');
        Route::get('/albums', [DashboardController::class, 'albums'])->name('albums');
        Route::get('/videos', [DashboardController::class, 'videos'])->name('videos');
        Route::get('/accounting', [DashboardController::class, 'accounting'])->name('accounting');

        // System Security & Master Config
        Route::get('/security-audit', [DashboardController::class, 'securityAudit'])->name('security-audit');
        Route::get('/access-control', [DashboardController::class, 'accessControl'])->name('access-control');
        Route::post('/access-control', [DashboardController::class, 'storeAdmin'])->name('access-control.store');
        Route::patch('/access-control/{id}/password', [DashboardController::class, 'updateAdminPassword'])->name('access-control.update-password');
        Route::delete('/access-control/{id}', [DashboardController::class, 'deleteAdmin'])->name('access-control.delete');
        Route::get('/activity-logs', [DashboardController::class, 'activityLogs'])->name('activity-logs');
        
        Route::prefix('config')->name('config.')->group(function () {
            Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
            Route::post('/settings', [DashboardController::class, 'updateSettings'])->name('settings.update');
            Route::get('/file-explorer', [DashboardController::class, 'fileExplorer'])->name('file-explorer');
            Route::delete('/file-explorer', [DashboardController::class, 'deleteFile'])->name('file-explorer.delete');
            Route::get('/system-repair', [DashboardController::class, 'systemRepair'])->name('system-repair');
            Route::get('/legal', [DashboardController::class, 'legal'])->name('legal');
            Route::get('/db-logs', [DashboardController::class, 'dbLogs'])->name('db-logs');
            Route::get('/ip-tool', [DashboardController::class, 'ipTool'])->name('ip-tool');
        });
        Route::get('/email-settings', [DashboardController::class, 'emailSettings'])->name('email-settings');
        Route::post('/email-settings', [DashboardController::class, 'updateEmailSettings'])->name('email-settings.update');
        Route::post('/email-settings/test', [DashboardController::class, 'testEmailConnection'])->name('email-settings.test');
    });
});

// Event Bookings
Route::get('/verify-ticket/{reference}', [BookingController::class, 'verifyTicket'])->name('event.verify-ticket')->middleware('auth:admin');
Route::get('/event/book/{id}', [BookingController::class, 'showForm'])->name('event.book');
Route::post('/event/book/process', [BookingController::class, 'process'])->name('event.book.process');
Route::get('/event/verify-member', [BookingController::class, 'verifyMember'])->name('event.verify-member');
Route::post('/event/send-otp', [BookingController::class, 'sendOTP'])->name('event.send-otp');
Route::post('/event/verify-otp', [BookingController::class, 'verifyOTP'])->name('event.verify-otp');

// Legacy Image Fallback (Supports old /uploads/ paths)
Route::get('/uploads/{path}', function ($path) {
    if (file_exists(storage_path('app/public/' . $path))) {
        return response()->file(storage_path('app/public/' . $path));
    }
    // Deep search in photos folder
    if (file_exists(storage_path('app/public/photos/' . $path))) {
        return response()->file(storage_path('app/public/photos/' . $path));
    }
    abort(404);
})->where('path', '.*');



