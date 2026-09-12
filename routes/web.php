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
use App\Http\Controllers\Admin\WhatsAppController;

// 🚨 SYSTEM-LEVEL ASSET FAILSAFE (MUST BE TOP)
Route::get('/img', function () {
    $p = request('p');
    if (!$p)
        return "Missing 'p' parameter.";

    // 🛡️ Clean up path from double-prefixes
    $p = str_replace(['img?p=', 'storage/', '/storage/', 'uploads/'], '', $p);
    $p = ltrim($p, '/');

    $f = storage_path('app/public/' . $p);
    if (!file_exists($f))
        $f = storage_path('app/public/cms/' . $p);
    if (!file_exists($f))
        $f = storage_path('app/public/photos/' . $p);
    if (!file_exists($f))
        $f = storage_path('app/public/uploads/' . $p);

    if (!file_exists($f) || is_dir($f))
        return "File not found at: $f";
    return response()->file($f);
});

// 🟢 TOP PRIORITY: UNIVERSAL ASSET INTERCEPTOR (Bypassing server blocks)
Route::any('{any_prefix?}/{media_type}/{path}', function ($prefix, $type, $path) {
    if (!in_array($type, ['media', 'storage', 'uploads', 'cdn']))
        return abort(404);
    $searchPaths = [
        storage_path('app/public/' . $path),
        storage_path('app/public/cms/' . $path),
        storage_path('app/public/uploads/' . $path),
        storage_path('app/public/photos/' . $path),
        storage_path($path),
    ];
    foreach ($searchPaths as $fullPath) {
        if (file_exists($fullPath) && !is_dir($fullPath))
            return response()->file($fullPath);
    }
    return abort(404);
})->where('any_prefix', 'public|.*')->where('media_type', 'media|storage|uploads|cdn')->where('path', '.*');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/path-debug', function () {
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

Route::get('/fix-storage', function () {
    try {
        $publicPath = public_path('storage');
        $msg = "";

        // 1. Remove the "Blocker" (Aggressive Mode)
        if (file_exists($publicPath)) {
            if (!is_link($publicPath)) {
                $backup = $publicPath . '_bak_' . time();
                if (@rename($publicPath, $backup)) {
                    $msg .= "Success: Moved blocker folder to backup. ";
                } else {
                    @unlink($publicPath); // Try deleting if it's a file
                    @rmdir($publicPath);  // Try deleting if it's an empty dir
                    $msg .= "Attempted removal of blocker folder. ";
                }
            } else {
                $msg .= "Existing symlink removed for Interceptor. ";
                @unlink($publicPath);
            }
        }

        // 2. Fix Permissions & Create Folders (CRITICAL FOR 419 ERRORS)
        $folders = [
            storage_path('app/public/photos'),
            storage_path('app/public/cms'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('logs'),
        ];

        foreach ($folders as $f) {
            if (!file_exists($f)) {
                @mkdir($f, 0775, true);
            }
            @chmod($f, 0775);
        }

        // 3. Clear sessions and Vite hot file
        $msg .= "Sessions cleared. ";
        $sessionPath = storage_path('framework/sessions');
        $files = glob($sessionPath . '/*');
        foreach ($files as $file) {
            if (is_file($file))
                @unlink($file);
        }

        $hotFile = public_path('hot');
        if (file_exists($hotFile)) {
            @unlink($hotFile);
            $msg .= "Removed Vite 'hot' file. ";
        }

        // 4. Clear Blade & Config Cache (Force Production Mode)
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        $msg .= "Site cache cleared. ";

        return "SUCCESS: Server reset to production mode! $msg <a href='/'>Go Home</a>";
    } catch (\Exception $e) {
        return "ERROR: " . $e->getMessage();
    }
});

Route::get('/fix-ids', function () {
    try {
        $count = 0;
        $fields = [
            'membership_id_assigned',
            'full_name',
            'email',
            'mobile_number',
            'spouse_name',
            'spouse_mobile',
            'post_code',
            'house_details',
            'transaction_ref',
            'bank_account_holder',
            'dob',
            'spouse_dob',
            'photo',
            'family_photo'
        ];

        $members = \App\Models\Member::all();
        foreach ($members as $m) {
            $update = [];
            foreach ($fields as $field) {
                $raw = $m->getRawOriginal($field);
                if (str_starts_with($raw, 'eyJ')) {
                    try {
                        $update[$field] = \Illuminate\Support\Facades\Crypt::decryptString($raw);
                    } catch (\Exception $e) {
                    }
                }
            }

            if (!empty($update)) {
                \Illuminate\Support\Facades\DB::table('members')->where('id', $m->id)->update($update);
                $count++;
            }
        }
        return "SUCCESS: Cleaned up $count membership profiles! <a href='/admin/members'>Go Home</a>";
    } catch (\Exception $e) {
        return "ERROR: " . $e->getMessage();
    }
});

Route::get('/clear-everything', function () {
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

Route::get('/run-migrations', function () {
    try {
        $output = [];

        // 1. Direct DB Failsafe SQL
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `members` MODIFY `membership_type` TEXT NULL");
            $output[] = "Direct DB: Updated members.membership_type to TEXT.";
        } catch (\Exception $e) {
            $output[] = "Direct DB members warning: " . $e->getMessage();
        }

        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `members` MODIFY `marital_status` TEXT NULL");
            $output[] = "Direct DB: Updated members.marital_status to TEXT.";
        } catch (\Exception $e) {
            $output[] = "Direct DB members marital_status warning: " . $e->getMessage();
        }

        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `renewal_requests` MODIFY `membership_type` TEXT NULL");
            $output[] = "Direct DB: Updated renewal_requests.membership_type to TEXT.";
        } catch (\Exception $e) {
            $output[] = "Direct DB renewal_requests warning: " . $e->getMessage();
        }

        // Add student_requests columns if missing
        $studentCols = [
            'full_name' => "TEXT NULL",
            'email' => "TEXT NULL",
            'phone' => "TEXT NULL",
            'university' => "TEXT NULL",
            'study_year' => "TEXT NULL",
            'status' => "VARCHAR(50) DEFAULT 'pending'"
        ];

        foreach ($studentCols as $col => $type) {
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `student_requests` ADD COLUMN `$col` $type");
                $output[] = "Added column student_requests.$col";
            } catch (\Exception $e) {
                // Column might already exist
            }
        }

        // 2. Run Artisan Migrate
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $artisanOutput = \Illuminate\Support\Facades\Artisan::output();
            $output[] = "Artisan Migrate Output:\n" . $artisanOutput;
        } catch (\Exception $e) {
            $output[] = "Artisan Migrate warning: " . $e->getMessage();
        }

        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');

        return "<pre>DATABASE FIX SUCCESSFUL:\n\n" . implode("\n", $output) . "</pre><br><a href='/'>Go to Home Page</a>";
    } catch (\Exception $e) {
        return "ERROR: " . $e->getMessage();
    }
});
Route::get('/fix-database', fn() => redirect('/run-migrations'));

Route::get('/membership', [MembershipController::class, 'index'])->name('membership');
Route::get('/id-card/view/{guid}', [MembershipController::class, 'viewIdCard'])->name('member.id-card.view');
Route::post('/api/membership/send-otp', [MembershipController::class, 'sendOtp']);
Route::post('/api/membership/verify-otp', [MembershipController::class, 'verifyOtp']);
Route::post('/membership/submit', [MembershipController::class, 'submit'])->name('membership.submit')->middleware(\App\Http\Middleware\CheckHoneypot::class);

// Telegram Smart Webhook Routes
Route::post('/telegram/webhook', [\App\Http\Controllers\TelegramWebhookController::class, 'handle'])->name('telegram.webhook');
Route::get('/telegram/set-webhook', function () {
    $token = \App\Services\TelegramService::getToken();
    if (!$token) {
        return response()->json(['status' => 'error', 'message' => 'Telegram bot token is not configured in settings.'], 400);
    }
    $webhookUrl = url('/telegram/webhook');
    if (str_starts_with($webhookUrl, 'http://')) {
        $webhookUrl = str_replace('http://', 'https://', $webhookUrl);
    }
    $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
        'url' => $webhookUrl
    ]);
    return response()->json([
        'status' => $response->successful() ? 'success' : 'failed',
        'webhook_url' => $webhookUrl,
        'telegram_response' => $response->json()
    ]);
})->name('telegram.set-webhook');

Route::get('/events', [EventController::class, 'index'])->name('events');
Route::get('/events/{id}', [EventController::class, 'show'])->name('events.show');

Route::get('/news', [NewsController::class, 'index'])->name('news');
Route::get('/news/{id}', [NewsController::class, 'show'])->name('news.show');

Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');
Route::get('/videos', [GalleryController::class, 'videos'])->name('public.videos');
Route::get('/offers', [OfferController::class, 'index'])->name('offers');
Route::get('/team', [TeamController::class, 'index'])->name('team');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/privacy', function () {
    return view('privacy');
})->name('privacy');

Route::get('/terms', function () {
    return view('terms');
})->name('terms');

Route::get('/cookie-policy', function () {
    return view('cookie_policy');
})->name('cookie-policy');

Route::get('/refund-policy', function () {
    return view('refund_policy');
})->name('refund-policy');

Route::get('/safeguarding', function () {
    return view('safeguarding');
})->name('safeguarding');

Route::get('/code-of-conduct', function () {
    return view('code_of_conduct');
})->name('code-of-conduct');

Route::get('/accessibility', function () {
    return view('accessibility');
})->name('accessibility');

Route::get('/student-corner', function () {
    return view('student_corner');
})->name('student-corner');
Route::post('/student-submit', [MembershipController::class, 'submitStudentRequest'])->name('student.submit');

// Admin Authentication (Entry URL: /tomjsailor)
Route::get('/tomjsailor', [AdminAuthController::class, 'showLogin'])->name('admin.login')->middleware('throttle:login');
Route::post('/tomjsailor', [AdminAuthController::class, 'login'])->middleware('throttle:login');

// Safe entry points
Route::get('/admin/login', fn () => redirect()->route('admin.login'));
Route::get('/admin', fn () => redirect()->route('admin.dashboard'));

Route::prefix('admin')->name('admin.')->group(function () {
    // Also support /admin/tomjsailor
    Route::get('/tomjsailor', [AdminAuthController::class, 'showLogin'])->middleware('throttle:login');
    Route::post('/tomjsailor', [AdminAuthController::class, 'login'])->middleware('throttle:login');

    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // ── 2FA Challenge (mid-login, no auth guard needed yet) ──
    Route::get('/2fa/challenge', [\App\Http\Controllers\Admin\TwoFactorController::class, 'showChallenge'])->name('2fa.challenge');
    Route::post('/2fa/challenge', [\App\Http\Controllers\Admin\TwoFactorController::class, 'verifyChallenge'])->name('2fa.verify');

    Route::middleware('auth:admin')->group(function () {
        Route::middleware('admin.role:admin,superadmin')->group(function () {
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
                Route::get('/renewals/{id}/details', [DashboardController::class, 'getRenewalDetails'])->name('renewal-details');
                Route::post('/renewals/{id}/approve', [DashboardController::class, 'approveRenewal'])->name('renewals.approve');
                Route::post('/{id}/approve', [DashboardController::class, 'approveMember'])->name('approve');
                Route::get('/{id}/details', [DashboardController::class, 'getMemberDetails'])->name('details');
                Route::post('/{id}/update', [DashboardController::class, 'updateMember'])->name('update');
                Route::delete('/{id}', [DashboardController::class, 'deleteMember'])->name('delete');
                Route::post('/{id}/restore', [DashboardController::class, 'restoreMember'])->name('restore');
                Route::post('/{id}/send-card-email', [DashboardController::class, 'sendCardEmail'])->name('send-card-email');
                Route::post('/{id}/send-card-whatsapp', [DashboardController::class, 'sendCardWhatsApp'])->name('send-card-whatsapp');
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
            Route::get('/news/{id}/details', [DashboardController::class, 'getNewsDetails'])->name('news.details');
            Route::post('/news/{id}/update', [DashboardController::class, 'updateNews'])->name('news.update');
            Route::delete('/news/{id}', [DashboardController::class, 'deleteNews'])->name('news.delete');

            // Events & Pricing
            Route::prefix('events')->name('events.')->group(function () {
                Route::get('/', [DashboardController::class, 'events'])->name('index');
                Route::post('/', [DashboardController::class, 'addEvent'])->name('add');
                Route::post('/{id}', [DashboardController::class, 'updateEvent'])->name('update');
                Route::delete('/{id}', [DashboardController::class, 'deleteEvent'])->name('delete');
                Route::get('/bookings', [EventBookingController::class, 'index'])->name('bookings');
                Route::get('/bookings/export', [EventBookingController::class, 'exportPdf'])->name('bookings.export');
                Route::get('/bookings/export-csv', [EventBookingController::class, 'exportCsv'])->name('bookings.export_csv');
                Route::get('/bookings/stats', [EventBookingController::class, 'stats'])->name('bookings.stats');
                Route::get('/bookings/{id}/status/{status}', [EventBookingController::class, 'updateStatus'])->name('bookings.status');
                Route::get('/bookings/{id}/resend', [EventBookingController::class, 'resendTicket'])->name('bookings.resend');
                Route::get('/bookings/{id}/edit', [EventBookingController::class, 'edit'])->name('bookings.edit');
                Route::post('/bookings/{id}/update', [EventBookingController::class, 'update'])->name('bookings.update');
                Route::delete('/bookings/{id}', [EventBookingController::class, 'destroy'])->name('bookings.delete');
                Route::get('/fare-logic', [DashboardController::class, 'fareLogic'])->name('fare-logic');
                Route::post('/fare-logic/categories', [DashboardController::class, 'saveFareCategory'])->name('fare-logic.categories.save');
                Route::delete('/fare-logic/categories/{id}', [DashboardController::class, 'deleteFareCategory'])->name('fare-logic.categories.delete');
                Route::post('/fare-logic/rubrics', [DashboardController::class, 'saveFareRubric'])->name('fare-logic.rubrics.save');
                Route::delete('/fare-logic/rubrics/{id}', [DashboardController::class, 'deleteFareRubric'])->name('fare-logic.rubrics.delete');

                Route::get('/stats', [EventBookingController::class, 'stats'])->name('stats');
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
            Route::post('/albums', [DashboardController::class, 'storeAlbum'])->name('albums.store');
            Route::delete('/albums/{id}', [DashboardController::class, 'deleteAlbum'])->name('albums.delete');
            Route::get('/videos', [DashboardController::class, 'videos'])->name('videos');
            Route::post('/videos', [DashboardController::class, 'storeVideo'])->name('videos.store');
            Route::delete('/videos/{id}', [DashboardController::class, 'deleteVideo'])->name('videos.delete');
            Route::get('/accounting', [DashboardController::class, 'accounting'])->name('accounting');
            Route::get('/accounting/export', [DashboardController::class, 'exportTransactions'])->name('accounting.export');
            Route::post('/accounting/sync', [DashboardController::class, 'syncFinancials'])->name('accounting.sync');
            Route::post('/accounting/revoke', [DashboardController::class, 'revokeReconciliation'])->name('accounting.revoke');
            Route::post('/accounting', [DashboardController::class, 'storeTransaction'])->name('accounting.store');
            Route::get('/accounting/{id}/details', [DashboardController::class, 'getTransactionDetails'])->name('accounting.details');
            Route::post('/accounting/{id}/update', [DashboardController::class, 'updateTransaction'])->name('accounting.update');
            Route::delete('/accounting/{id}', [DashboardController::class, 'deleteTransaction'])->name('accounting.delete');

            // System Security & Master Config
            Route::get('/security-audit', [DashboardController::class, 'securityAudit'])->name('security-audit');
            Route::post('/security-audit/waf', [DashboardController::class, 'updateWafSettings'])->name('security-audit.waf.update');
            Route::get('/access-control', [DashboardController::class, 'accessControl'])->name('access-control');
            Route::post('/access-control', [DashboardController::class, 'storeAdmin'])->name('access-control.store');
            Route::patch('/access-control/{id}', [DashboardController::class, 'updateAdmin'])->name('access-control.update');
            Route::patch('/access-control/{id}/password', [DashboardController::class, 'updateAdminPassword'])->name('access-control.update-password');
            Route::delete('/access-control/{id}', [DashboardController::class, 'deleteAdmin'])->name('access-control.delete');
            Route::get('/activity-logs', [DashboardController::class, 'activityLogs'])->name('activity-logs');

            Route::prefix('config')->name('config.')->group(function () {
                Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
                Route::post('/settings', [DashboardController::class, 'updateSettings'])->name('settings.update');
                Route::get('/file-explorer', [DashboardController::class, 'fileExplorer'])->name('file-explorer');
                Route::delete('/file-explorer', [DashboardController::class, 'deleteFile'])->name('file-explorer.delete');
                Route::get('/system-repair', [DashboardController::class, 'systemRepair'])->name('system-repair');
                Route::post('/system-repair', [DashboardController::class, 'runSystemRepair'])->name('system-repair.run');
                Route::get('/legal', [DashboardController::class, 'legal'])->name('legal');
                Route::get('/db-logs', [DashboardController::class, 'dbLogs'])->name('db-logs');
                Route::post('/db-logs/clear', [DashboardController::class, 'clearSystemLogs'])->name('db-logs.clear');
                Route::post('/db-logs/whatsapp-clear', [DashboardController::class, 'clearWhatsAppLogs'])->name('db-logs.whatsapp-clear');
                Route::get('/ip-tool', [DashboardController::class, 'ipTool'])->name('ip-tool');
                Route::get('/terminal', [DashboardController::class, 'terminal'])->name('terminal');
                Route::post('/terminal/run', [DashboardController::class, 'runTerminalCommand'])->name('terminal.run');
            });

            Route::get('/email-settings', [DashboardController::class, 'emailSettings'])->name('email-settings');
            Route::post('/email-settings', [DashboardController::class, 'updateEmailSettings'])->name('email-settings.update');

            // WhatsApp Automation Hub
            Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
                Route::get('/', [WhatsAppController::class, 'index'])->name('index');
                Route::get('/status', [WhatsAppController::class, 'status'])->name('status');
                Route::get('/qr', [WhatsAppController::class, 'qr'])->name('qr');
                Route::post('/send-test', [WhatsAppController::class, 'sendTest'])->name('send-test');
                Route::post('/test-admin-alert', [WhatsAppController::class, 'testAdminAlert'])->name('test-admin-alert');
                Route::post('/broadcast', [WhatsAppController::class, 'broadcast'])->name('broadcast');
                Route::post('/broadcast/{id}/dispatch-batch', [WhatsAppController::class, 'dispatchBatch'])->name('broadcast.dispatch-batch');
                Route::get('/broadcast/{id}/details', [WhatsAppController::class, 'getBroadcastDetails'])->name('broadcast.details');
                Route::post('/broadcast/{id}/resume', [WhatsAppController::class, 'resumeBroadcast'])->name('broadcast.resume');
                Route::post('/broadcast/{id}/retry-failed', [WhatsAppController::class, 'retryFailedRecipients'])->name('broadcast.retry-failed');
                Route::post('/broadcast/{id}/revoke', [WhatsAppController::class, 'revokeBroadcast'])->name('broadcast.revoke');
                Route::post('/broadcast/{id}/revoke-recipient/{index}', [WhatsAppController::class, 'revokeRecipientMessage'])->name('broadcast.revoke-recipient');
                Route::post('/message/revoke', [WhatsAppController::class, 'revokeDirectMessage'])->name('message.revoke');
                Route::get('/favorites', [WhatsAppController::class, 'getFavorites'])->name('favorites.get');
                Route::post('/favorites', [WhatsAppController::class, 'saveFavorite'])->name('favorites.save');
                Route::delete('/favorites/{id}', [WhatsAppController::class, 'deleteFavorite'])->name('favorites.delete');
                Route::get('/scheduled', [WhatsAppController::class, 'getScheduled'])->name('scheduled.get');
                Route::post('/scheduled/{id}/cancel', [WhatsAppController::class, 'cancelScheduled'])->name('scheduled.cancel');
                Route::post('/scheduled/{id}/send-now', [WhatsAppController::class, 'sendNowScheduled'])->name('scheduled.send-now');
                Route::delete('/scheduled/{id}', [WhatsAppController::class, 'deleteScheduled'])->name('scheduled.delete');
                Route::post('/scheduled/process', [WhatsAppController::class, 'processScheduled'])->name('scheduled.process');
                Route::post('/logout', [WhatsAppController::class, 'logout'])->name('logout');
                Route::post('/revoke', [WhatsAppController::class, 'revoke'])->name('revoke');
                Route::get('/logs', [WhatsAppController::class, 'logs'])->name('logs');
                Route::post('/logs/clear', [WhatsAppController::class, 'clearLogs'])->name('logs.clear');
            });
            Route::post('/email-settings/test', [DashboardController::class, 'testEmailConnection'])->name('email-settings.test');
        });

        // Staff Counter Dashboard
        Route::prefix('staff')->name('staff.')->middleware('admin.role:staff,admin,superadmin')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\StaffController::class, 'dashboard'])->name('dashboard');
            Route::get('/search', [\App\Http\Controllers\Admin\StaffController::class, 'search'])->name('search');
            Route::post('/check-in', [\App\Http\Controllers\Admin\StaffController::class, 'checkIn'])->name('check-in');
            Route::get('/recent', [\App\Http\Controllers\Admin\StaffController::class, 'getRecentCheckIns'])->name('recent');
        });
    });
});

// Event Bookings
Route::get('/verify-ticket/{reference}', [BookingController::class, 'verifyTicket'])->name('event.verify-ticket')->middleware(['auth:admin', 'admin.role:staff,admin,superadmin']);
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

// Redirect legacy .php URLs
Route::get('/gallery.php', function () {
    return redirect()->route('gallery', request()->query());
});
Route::get('/videos.php', function () {
    return redirect()->route('public.videos', request()->query());
});



