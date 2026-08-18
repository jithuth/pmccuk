<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;
use App\Models\Menu;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        \Illuminate\Pagination\Paginator::useBootstrapFive();
        
        try {
            if (Schema::hasTable('settings')) {
                $settings = Setting::all()->pluck('setting_value', 'setting_key');
                
                // 1. Decryption Logic for Shared Settings
                $encryptedFields = [
                    'site_name', 'contact_email', 'contact_phone', 'contact_address',
                    'mail_host', 'mail_username', 'mail_password', 'mail_from_address', 'mail_from_name',
                    'social_facebook', 'social_instagram', 'social_youtube', 'social_twitter',
                    'bank_account_no', 'bank_sort_code',
                    'legal_privacy_policy', 'legal_terms_conditions', 'legal_cookie_policy',
                    'legal_refund_cancellation', 'legal_safeguarding', 'legal_code_of_conduct', 'legal_accessibility',
                    'telegram_bot_token', 'telegram_chat_id', 'waf_enabled', 'waf_ip_blocklist'
                ];
                
                foreach ($encryptedFields as $field) {
                    if ($settings->has($field) && !empty($settings[$field])) {
                        try {
                            $settings[$field] = decrypt($settings[$field]);
                        } catch (\Exception $e) {
                            // Already plaintext or key mismatch
                        }
                    }
                }
                
                // 2. Dynamic Mail Configuration
                if ($settings->get('mail_host')) {
                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.transport' => 'smtp',
                        'mail.mailers.smtp.host' => $settings->get('mail_host'),
                        'mail.mailers.smtp.port' => $settings->get('mail_port', 587),
                        'mail.mailers.smtp.username' => $settings->get('mail_username'),
                        'mail.mailers.smtp.password' => $settings->get('mail_password'),
                        'mail.mailers.smtp.encryption' => $settings->get('mail_encryption', 'tls'),
                        'mail.from.address' => $settings->get('mail_from_address', 'noreply@pmcc.uk'),
                        'mail.from.name' => $settings->get('mail_from_name', 'PMCC UK'),
                    ]);
                }

                // 2. View Sharing
                $menus = [];
                if (Schema::hasTable('menus')) {
                    $menusRaw = Menu::where('parent_id', 0)->where('is_active', 1)->orderBy('order_no')->get();
                    foreach($menusRaw as $m) {
                        $url = $m->url;
                        if ($url !== '#') {
                            $url = str_replace('.php', '', $url);
                        }
                        
                        $submenus = [];
                        $submenusRaw = Menu::where('parent_id', $m->id)->where('is_active', 1)->orderBy('order_no')->get();
                        foreach ($submenusRaw as $sub) {
                            $subUrl = $sub->url;
                            if ($subUrl !== '#') {
                                $subUrl = str_replace('.php', '', $subUrl);
                            }
                            $submenus[] = [
                                'title' => $sub->title,
                                'url' => $subUrl
                            ];
                        }

                        $menus[] = [
                            'title' => $m->title,
                            'url' => $url,
                            'submenus' => $submenus
                        ];
                    }
                }

                View::share('settings', $settings);
                View::share('menus', $menus);
            }
        } catch (\Throwable $e) {
            // Gracefully ignore database connection errors on boot/CLI
        }

        // 🚨 SHARED HOSTING ASSET OVERRIDE
        // Replaces all /storage/ paths with highly-compatible /img?p= query paths
        \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
        \Illuminate\Support\Facades\URL::macro('asset_compat', function($path) {
            if (str_contains($path, 'storage/')) {
                $p = explode('storage/', $path)[1];
                return url('/img?p=' . $p);
            }
            if (str_contains($path, 'uploads/')) {
                $p = explode('uploads/', $path)[1];
                return url('/img?p=' . $p);
            }
            return url($path);
        });
    }
}
