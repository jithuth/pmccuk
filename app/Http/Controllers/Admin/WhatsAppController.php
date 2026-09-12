<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OpenWaService;
use App\Models\WhatsAppScheduledBroadcast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

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

        $scheduledBroadcasts = WhatsAppScheduledBroadcast::orderBy('created_at', 'desc')->take(25)->get();

        return view('admin.whatsapp.index', compact('status', 'settings', 'counts', 'favorites', 'scheduledBroadcasts'));
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
     * Dispatch or Schedule Targeted Community Broadcast
     * Supports: text, multiple images, multiple documents, URLs, contact cards, and scheduling
     */
    public function broadcast(Request $request)
    {
        $request->validate([
            'audience' => 'required|string|in:members,attendees,students,executives,custom',
            'message' => 'required|string|max:4000',
            'custom_numbers' => 'nullable|string',
            'selected_executives' => 'nullable',
            'schedule_mode' => 'nullable|string|in:now,scheduled',
            'scheduled_at' => 'nullable|string',
            'images.*' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif|max:10240',
            'documents.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip|max:25600',
            'urls' => 'nullable',
            'contacts' => 'nullable'
        ]);

        $audience = $request->input('audience');
        $message = trim($request->input('message'));
        $recipients = [];

        // 1. Resolve recipients
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
            if (is_string($selectedIds)) {
                $selectedIds = json_decode($selectedIds, true) ?: [$selectedIds];
            }

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

        // 2. Handle uploaded Images
        $attachmentImages = [];
        if ($request->hasFile('images')) {
            $storageDir = storage_path('app/public/whatsapp_broadcasts');
            if (!file_exists($storageDir)) {
                @mkdir($storageDir, 0755, true);
            }

            foreach ($request->file('images') as $file) {
                if ($file && $file->isValid()) {
                    $ext = $file->getClientOriginalExtension() ?: 'jpg';
                    $safeName = time() . '_' . uniqid() . '.' . $ext;
                    $file->move($storageDir, $safeName);
                    $attachmentImages[] = [
                        'name' => $file->getClientOriginalName(),
                        'filename' => $safeName,
                        'path' => $storageDir . DIRECTORY_SEPARATOR . $safeName,
                        'url' => asset('storage/whatsapp_broadcasts/' . $safeName),
                        'mimetype' => $file->getClientMimeType() ?: 'image/jpeg',
                        'caption' => ''
                    ];
                }
            }
        }

        // 3. Handle uploaded Documents
        $attachmentDocs = [];
        if ($request->hasFile('documents')) {
            $storageDir = storage_path('app/public/whatsapp_broadcasts');
            if (!file_exists($storageDir)) {
                @mkdir($storageDir, 0755, true);
            }

            foreach ($request->file('documents') as $file) {
                if ($file && $file->isValid()) {
                    $ext = $file->getClientOriginalExtension() ?: 'pdf';
                    $safeName = time() . '_' . uniqid() . '.' . $ext;
                    $file->move($storageDir, $safeName);
                    $attachmentDocs[] = [
                        'name' => $file->getClientOriginalName(),
                        'filename' => $safeName,
                        'path' => $storageDir . DIRECTORY_SEPARATOR . $safeName,
                        'url' => asset('storage/whatsapp_broadcasts/' . $safeName),
                        'mimetype' => $file->getClientMimeType() ?: 'application/pdf',
                        'caption' => ''
                    ];
                }
            }
        }

        // 4. Parse URLs
        $urls = [];
        if ($request->filled('urls')) {
            $rawUrls = $request->input('urls');
            $urls = is_array($rawUrls) ? $rawUrls : (json_decode($rawUrls, true) ?: []);
        }

        // 5. Parse Contacts
        $contacts = [];
        if ($request->filled('contacts')) {
            $rawContacts = $request->input('contacts');
            $contacts = is_array($rawContacts) ? $rawContacts : (json_decode($rawContacts, true) ?: []);
        }

        $attachments = [
            'images' => $attachmentImages,
            'documents' => $attachmentDocs,
            'urls' => $urls,
            'contacts' => $contacts
        ];

        // 6. Check Scheduling vs Immediate Dispatch
        $scheduleMode = $request->input('schedule_mode', 'now');
        $scheduledAtInput = $request->input('scheduled_at');

        if ($scheduleMode === 'scheduled' && !empty($scheduledAtInput)) {
            try {
                $scheduledAt = Carbon::parse($scheduledAtInput, 'Europe/London');
            } catch (\Throwable $e) {
                $scheduledAt = Carbon::now()->addMinutes(5);
            }

            $selectedExecs = $request->input('selected_executives');
            if (is_string($selectedExecs)) {
                $selectedExecs = json_decode($selectedExecs, true) ?: [$selectedExecs];
            }

            $broadcast = WhatsAppScheduledBroadcast::create([
                'title' => 'Broadcast to ' . ucfirst($audience) . ' (' . count($deduped) . ' recipients)',
                'audience' => $audience,
                'selected_executives' => $selectedExecs,
                'custom_numbers' => $request->input('custom_numbers'),
                'message' => $message,
                'attachments' => $attachments,
                'scheduled_at' => $scheduledAt,
                'status' => 'pending',
                'total_recipients' => count($deduped),
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'scheduled' => true,
                'broadcast_id' => $broadcast->id,
                'message' => "Community Broadcast scheduled for " . $scheduledAt->format('d M Y, h:i A') . " UK Time (" . count($deduped) . " recipients).",
                'broadcast' => $broadcast
            ]);
        }

        // Immediate Dispatch
        $broadcast = WhatsAppScheduledBroadcast::create([
            'title' => 'Broadcast to ' . ucfirst($audience) . ' (' . count($deduped) . ' recipients)',
            'audience' => $audience,
            'selected_executives' => is_array($request->input('selected_executives')) ? $request->input('selected_executives') : json_decode($request->input('selected_executives', '[]'), true),
            'custom_numbers' => $request->input('custom_numbers'),
            'message' => $message,
            'attachments' => $attachments,
            'scheduled_at' => null,
            'executed_at' => Carbon::now(),
            'status' => 'processing',
            'total_recipients' => count($deduped),
            'created_by' => auth()->id()
        ]);

        $sent = 0;
        $failed = 0;
        $logs = [];

        foreach ($deduped as $r) {
            $ok = OpenWaService::dispatchBroadcastBundle(
                $r['phone'],
                $r['name'],
                $message,
                $attachments
            );

            if ($ok) {
                $sent++;
                $logs[] = "[SUCCESS] Dispatched to {$r['name']} ({$r['phone']})";
            } else {
                $failed++;
                $logs[] = "[FAILED] Failed to dispatch to {$r['name']} ({$r['phone']})";
            }

            usleep(350000); // 350ms delay
        }

        $broadcast->update([
            'status' => $sent > 0 ? 'completed' : 'failed',
            'sent_count' => $sent,
            'failed_count' => $failed,
            'error_log' => implode("\n", array_slice($logs, 0, 100))
        ]);

        return response()->json([
            'success' => true,
            'scheduled' => false,
            'broadcast_id' => $broadcast->id,
            'message' => "Broadcast complete! {$sent} dispatched successfully, {$failed} failed.",
            'stats' => ['sent' => $sent, 'failed' => $failed, 'total' => count($deduped)],
            'broadcast' => $broadcast
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

    /**
     * Get list of scheduled broadcasts (JSON)
     */
    public function getScheduled()
    {
        $broadcasts = WhatsAppScheduledBroadcast::orderBy('created_at', 'desc')->take(50)->get();

        return response()->json([
            'success' => true,
            'broadcasts' => $broadcasts
        ]);
    }

    /**
     * Cancel a pending scheduled broadcast
     */
    public function cancelScheduled($id)
    {
        $broadcast = WhatsAppScheduledBroadcast::findOrFail($id);

        if ($broadcast->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending broadcasts can be cancelled.'
            ], 422);
        }

        $broadcast->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => "Scheduled broadcast #{$id} has been cancelled.",
            'broadcast' => $broadcast
        ]);
    }

    /**
     * Trigger immediate execution of a scheduled broadcast
     */
    public function sendNowScheduled($id)
    {
        $broadcast = WhatsAppScheduledBroadcast::findOrFail($id);

        if ($broadcast->status === 'completed' || $broadcast->status === 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'This broadcast is already ' . $broadcast->status
            ], 422);
        }

        // Reset scheduled_at to now and mark pending
        $broadcast->update([
            'status' => 'pending',
            'scheduled_at' => Carbon::now()->subSecond()
        ]);

        // Run artisan processor immediately
        Artisan::call('whatsapp:process-scheduled');
        $fresh = $broadcast->fresh();

        return response()->json([
            'success' => true,
            'message' => "Broadcast #{$id} triggered for immediate dispatch.",
            'broadcast' => $fresh
        ]);
    }

    /**
     * Delete a scheduled broadcast record
     */
    public function deleteScheduled($id)
    {
        $broadcast = WhatsAppScheduledBroadcast::findOrFail($id);
        $broadcast->delete();

        return response()->json([
            'success' => true,
            'message' => "Broadcast record #{$id} deleted successfully."
        ]);
    }

    /**
     * Internal endpoint for daemon ticker to trigger scheduled broadcast runner
     */
    public function processScheduled()
    {
        Artisan::call('whatsapp:process-scheduled');
        $output = Artisan::output();

        return response()->json([
            'success' => true,
            'output' => trim($output)
        ]);
    }
}
