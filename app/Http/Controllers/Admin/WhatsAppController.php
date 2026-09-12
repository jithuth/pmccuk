<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OpenWaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    /**
     * Display WhatsApp Console Dashboard
     */
    public function index()
    {
        $status = OpenWaService::getStatus();
        $isEnabled = OpenWaService::isEnabled();
        $serverUrl = OpenWaService::getServerUrl();
        $apiKey = OpenWaService::getApiKey();

        $settings = [
            'enabled' => $isEnabled,
            'server_url' => $serverUrl,
            'notify_id_card' => (string) OpenWaService::getSetting('whatsapp_notify_id_card', '1') === '1',
            'notify_event_ticket' => (string) OpenWaService::getSetting('whatsapp_notify_event_ticket', '1') === '1',
            'notify_otp' => (string) OpenWaService::getSetting('whatsapp_notify_otp', '1') === '1',
            'notify_admin_security' => (string) OpenWaService::getSetting('whatsapp_notify_admin_security', '0') === '1',
            'admin_numbers' => OpenWaService::getSetting('whatsapp_admin_numbers', ''),
        ];

        $rawFavorites = OpenWaService::getSetting('whatsapp_favorite_executives', '[]');
        $favorites = json_decode($rawFavorites, true) ?: [];

        if (empty($favorites)) {
            $adminNums = OpenWaService::getSetting('whatsapp_admin_numbers', '');
            if (!empty($adminNums)) {
                $numArr = array_filter(array_map('trim', preg_split('/[,\n;]+/', $adminNums)));
                $idx = 1;
                foreach ($numArr as $n) {
                    $favorites[] = [
                        'id' => (string) $idx++,
                        'name' => 'Admin Executive',
                        'phone' => $n,
                        'role' => 'Executive Committee'
                    ];
                }
            }
        }

        $counts = [
            'members' => \App\Models\Member::where('status', 'active')->whereNotNull('mobile_number')->count(),
            'attendees' => \App\Models\EventBooking::where('booking_status', 'approved')->whereNotNull('phone')->count(),
            'students' => \App\Models\StudentRequest::whereNotNull('phone')->count(),
            'executives' => count($favorites),
        ];

        return view('admin.whatsapp.index', compact('status', 'settings', 'counts', 'favorites'));
    }

    /**
     * AJAX endpoint for polling connection status
     */
    public function status()
    {
        $status = OpenWaService::getStatus();
        return response()->json($status);
    }

    /**
     * AJAX endpoint for polling current QR code
     */
    public function qr()
    {
        $qr = OpenWaService::getQr();
        $status = OpenWaService::getStatus();

        return response()->json([
            'success' => true,
            'qr' => $qr,
            'status' => $status['status'] ?? 'disconnected',
            'connected' => $status['connected'] ?? false
        ]);
    }

    /**
     * Send test message from the Admin Console
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:8|max:20',
            'message' => 'required|string|max:1000',
        ]);

        $phone = $request->input('phone');
        $message = $request->input('message');

        $sent = OpenWaService::sendText($phone, $message);

        if ($sent) {
            return response()->json([
                'success' => true,
                'message' => "Test message successfully dispatched to " . OpenWaService::formatPhone($phone)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "Failed to dispatch test message. Please ensure the WhatsApp daemon is connected and the phone number is valid."
        ], 422);
    }

    /**
     * Dispatch a synchronized Test Admin Alert to both WhatsApp and Telegram
     */
    public function testAdminAlert(Request $request)
    {
        $rawNumbers = $request->input('phone') ?: OpenWaService::getSetting('whatsapp_admin_numbers', '');
        
        if (empty($rawNumbers)) {
            return response()->json([
                'success' => false,
                'message' => 'No admin mobile number provided. Please enter an admin phone number in the field.'
            ], 422);
        }

        $testAlert = "🛡️ <b>[PMCC-UK ADMIN SECURITY ALERT]</b>\n\n" .
            "📅 <b>Timestamp:</b> " . now()->format('d M Y, H:i:s') . " UTC\n" .
            "🌐 <b>Environment:</b> Live Production (pmccuk.org)\n" .
            "👤 <b>Triggered By:</b> " . (auth()->user()?->name ?? 'Administrator') . "\n" .
            "⚡ <b>Pipeline:</b> Synchronized WhatsApp & Telegram Admin Alert\n\n" .
            "📝 <b>Alert Details:</b> This is an official verified test alert confirming that all system errors, intrusions, member activities, and transaction events are instantly delivered to your WhatsApp.";

        // 1. Dispatch to Telegram
        $telegramSent = false;
        try {
            $telegramSent = \App\Services\TelegramService::sendMessage($testAlert);
        } catch (\Throwable $e) {}

        // 2. Dispatch to WhatsApp
        $numList = array_filter(array_map('trim', preg_split('/[,\n;]+/', $rawNumbers)));
        $sentCount = 0;
        $targets = [];

        foreach ($numList as $n) {
            $formatted = OpenWaService::formatPhone($n);
            if ($formatted) {
                $targets[] = '+' . $formatted;
                $waMsg = OpenWaService::convertHtmlToWhatsAppMarkdown($testAlert);
                if (OpenWaService::sendText($n, $waMsg)) {
                    $sentCount++;
                }
            }
        }

        if ($sentCount > 0) {
            $tgNote = $telegramSent ? ' and Telegram channel' : '';
            return response()->json([
                'success' => true,
                'message' => "Admin test alert successfully delivered to {$sentCount} WhatsApp admin(s) (" . implode(', ', $targets) . "){$tgNote}!"
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "Failed to deliver WhatsApp test alert. Please verify your phone number and ensure the WhatsApp daemon is connected."
        ], 422);
    }

    /**
     * Logout & Unlink session from daemon
     */
    public function logout()
    {
        $success = OpenWaService::logout();

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'WhatsApp device has been unlinked successfully. You may now scan a new QR code.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to unlink device. Please check daemon connectivity.'
        ], 500);
    }

    /**
     * Revoke WhatsApp Session (Standard Unlink or Force Purge)
     */
    public function revoke(Request $request)
    {
        $force = $request->boolean('force', false);
        $result = OpenWaService::revoke($force);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    /**
     * Stream or Fetch WhatsApp Gateway & Bot Logs
     */
    public function logs(Request $request)
    {
        $limit = min((int) $request->input('limit', 100), 500);
        $type = $request->input('type');
        $sinceId = $request->has('since_id') ? (int) $request->input('since_id') : null;
        $search = $request->input('search');

        $data = OpenWaService::getLogs($limit, $type, $sinceId, $search);

        return response()->json($data);
    }

    /**
     * Clear WhatsApp Gateway Logs
     */
    public function clearLogs()
    {
        $success = OpenWaService::clearLogs();

        return response()->json([
            'success' => $success,
            'message' => $success ? 'WhatsApp Gateway logs cleared successfully.' : 'Failed to clear daemon logs.'
        ]);
    }

    /**
     * Dispatch Targeted Community Broadcast
     */
    public function broadcast(Request $request)
    {
        $request->validate([
            'audience' => 'required|string|in:members,attendees,students,executives,custom',
            'message' => 'required|string|max:2000',
            'custom_numbers' => 'nullable|string',
            'selected_executives' => 'nullable|array'
        ]);

        $audience = $request->input('audience');
        $message = $request->input('message');
        $recipients = [];

        if ($audience === 'members') {
            $members = \App\Models\Member::where('status', 'active')
                ->whereNotNull('mobile_number')
                ->get();
            foreach ($members as $m) {
                $recipients[] = [
                    'phone' => $m->mobile_number,
                    'name' => $m->full_name
                ];
            }
        } elseif ($audience === 'attendees') {
            $bookings = \App\Models\EventBooking::where('booking_status', 'approved')
                ->whereNotNull('phone')
                ->get();
            foreach ($bookings as $b) {
                $recipients[] = [
                    'phone' => $b->phone,
                    'name' => $b->full_name
                ];
            }
        } elseif ($audience === 'students') {
            $students = \App\Models\StudentRequest::whereNotNull('phone')->get();
            foreach ($students as $s) {
                $recipients[] = [
                    'phone' => $s->phone,
                    'name' => $s->full_name ?? 'Student'
                ];
            }
        } elseif ($audience === 'executives') {
            $raw = OpenWaService::getSetting('whatsapp_favorite_executives', '[]');
            $list = json_decode($raw, true) ?: [];
            $selectedIds = $request->input('selected_executives');

            foreach ($list as $item) {
                if (!empty($selectedIds) && is_array($selectedIds)) {
                    if (!in_array((string) ($item['id'] ?? ''), array_map('strval', $selectedIds))) {
                        continue;
                    }
                }
                if (!empty($item['phone'])) {
                    $recipients[] = [
                        'phone' => $item['phone'],
                        'name' => $item['name'] ?? 'Executive Member'
                    ];
                }
            }
        } elseif ($audience === 'custom') {
            $raw = $request->input('custom_numbers', '');
            $lines = preg_split('/[\r\n,]+/', $raw);
            foreach ($lines as $line) {
                $num = trim($line);
                if (!empty($num)) {
                    $recipients[] = ['phone' => $num, 'name' => 'Community Member'];
                }
            }
        }

        if (empty($recipients)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid recipients found for the selected audience segment.'
            ], 422);
        }

        // Deduplicate by phone
        $unique = [];
        $deduped = [];
        foreach ($recipients as $r) {
            $clean = OpenWaService::formatPhone($r['phone']);
            if ($clean && !isset($unique[$clean])) {
                $unique[$clean] = true;
                $deduped[] = $r;
            }
        }

        $res = OpenWaService::sendBroadcast($deduped, $message, null, null, 1);

        return response()->json([
            'success' => true,
            'message' => "Broadcast complete! {$res['sent']} dispatched successfully, {$res['failed']} failed.",
            'stats' => $res
        ]);
    }

    /**
     * Get list of favorite executive member numbers
     */
    public function getFavorites()
    {
        $raw = OpenWaService::getSetting('whatsapp_favorite_executives', '[]');
        $list = json_decode($raw, true) ?: [];
        return response()->json([
            'success' => true,
            'favorites' => array_values($list)
        ]);
    }

    /**
     * Save or update an executive favorite contact
     */
    public function saveFavorite(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|min:8|max:25',
            'role' => 'nullable|string|max:100',
            'id' => 'nullable|string'
        ]);

        $name = trim($request->input('name'));
        $phone = trim($request->input('phone'));
        $role = trim($request->input('role') ?: 'Executive Committee');

        $raw = OpenWaService::getSetting('whatsapp_favorite_executives', '[]');
        $list = json_decode($raw, true) ?: [];

        $id = $request->input('id');
        $found = false;

        if ($id) {
            foreach ($list as &$item) {
                if (isset($item['id']) && (string) $item['id'] === (string) $id) {
                    $item['name'] = $name;
                    $item['phone'] = $phone;
                    $item['role'] = $role;
                    $found = true;
                    break;
                }
            }
            unset($item);
        }

        if (!$found) {
            $id = (string) (time() . rand(100, 999));
            $list[] = [
                'id' => $id,
                'name' => $name,
                'phone' => $phone,
                'role' => $role
            ];
        }

        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'whatsapp_favorite_executives'],
            ['setting_value' => json_encode(array_values($list))]
        );

        return response()->json([
            'success' => true,
            'message' => "Saved {$name} ({$phone}) to Executive Favorites.",
            'favorites' => array_values($list)
        ]);
    }

    /**
     * Remove an executive favorite contact
     */
    public function deleteFavorite($id)
    {
        $raw = OpenWaService::getSetting('whatsapp_favorite_executives', '[]');
        $list = json_decode($raw, true) ?: [];

        $filtered = array_values(array_filter($list, function ($item) use ($id) {
            return (string) ($item['id'] ?? '') !== (string) $id;
        }));

        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'whatsapp_favorite_executives'],
            ['setting_value' => json_encode($filtered)]
        );

        return response()->json([
            'success' => true,
            'message' => 'Executive contact removed from favorites.',
            'favorites' => $filtered
        ]);
    }
}
