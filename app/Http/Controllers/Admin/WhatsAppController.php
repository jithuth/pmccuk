<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OpenWaService;
use App\Models\WhatsAppScheduledBroadcast;
use App\Models\WhatsAppTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
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
            'notify_event_ticket' => OpenWaService::isEventTicketsActive(),
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
        $templates = WhatsAppTemplate::orderBy('is_system', 'desc')->orderBy('name', 'asc')->get();

        return view('admin.whatsapp.index', compact('status', 'settings', 'counts', 'favorites', 'scheduledBroadcasts', 'templates'));
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
            'message' => 'required|string|max:4000',
        ]);

        $phone = $request->input('phone');
        $message = $request->input('message');

        // Resolve member name dynamically from sending phone number, or fallback to 'Community Member'
        $recipientInfo = OpenWaService::resolveRecipientByPhone($phone);
        $personalizedMessage = OpenWaService::personalizeMessage(
            $message,
            $recipientInfo['name'],
            $phone,
            $recipientInfo['membership_id']
        );

        $sent = OpenWaService::sendText($phone, $personalizedMessage);

        if ($sent) {
            return response()->json([
                'success' => true,
                'resolved_name' => $recipientInfo['name'],
                'message' => "Test message successfully dispatched to {$recipientInfo['name']} (" . OpenWaService::formatPhone($phone) . ")"
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

        // 1. Resolve recipients (with active member lookup for dynamic name personalization)
        $memberMap = OpenWaService::buildMemberPhoneMap();

        if ($audience === 'members') {
            $members = \App\Models\Member::where('status', 'active')
                ->whereNotNull('mobile_number')
                ->get();
            foreach ($members as $m) {
                $recipients[] = [
                    'phone' => $m->mobile_number,
                    'name' => trim((string) $m->full_name) ?: 'Community Member',
                    'membership_id' => $m->membership_id,
                ];
            }
        } elseif ($audience === 'attendees') {
            $bookings = \App\Models\EventBooking::where('booking_status', 'approved')
                ->whereNotNull('phone')
                ->get();
            foreach ($bookings as $b) {
                $resolved = OpenWaService::resolveRecipientByPhone($b->phone, $memberMap);
                $recipients[] = [
                    'phone' => $b->phone,
                    'name' => trim((string) $b->full_name) ?: $resolved['name'],
                    'membership_id' => $resolved['membership_id'],
                ];
            }
        } elseif ($audience === 'students') {
            $students = \App\Models\StudentRequest::whereNotNull('phone')->get();
            foreach ($students as $s) {
                $resolved = OpenWaService::resolveRecipientByPhone($s->phone, $memberMap);
                $recipients[] = [
                    'phone' => $s->phone,
                    'name' => trim((string) ($s->full_name ?? '')) ?: $resolved['name'],
                    'membership_id' => $resolved['membership_id'],
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
                    $resolved = OpenWaService::resolveRecipientByPhone($item['phone'], $memberMap);
                    $execName = trim((string) ($item['name'] ?? ''));
                    $recipients[] = [
                        'phone' => $item['phone'],
                        'name' => $execName !== '' ? $execName : $resolved['name'],
                        'membership_id' => $resolved['membership_id'],
                    ];
                }
            }
        } elseif ($audience === 'custom') {
            $raw = $request->input('custom_numbers', '');
            $lines = preg_split('/[\r\n,]+/', $raw);
            foreach ($lines as $line) {
                $num = trim($line);
                if (!empty($num)) {
                    $resolved = OpenWaService::resolveRecipientByPhone($num, $memberMap);
                    $recipients[] = [
                        'phone' => $num,
                        'name' => $resolved['name'],
                        'membership_id' => $resolved['membership_id'],
                    ];
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

        // 6. Build per-recipient audit and progress tracking data
        $recipientsData = [];
        foreach ($deduped as $idx => $r) {
            $recipientsData[] = [
                'id' => $idx + 1,
                'phone' => $r['phone'],
                'name' => $r['name'],
                'status' => 'pending', // 'pending', 'sent', 'failed'
                'sent_at' => null,
                'error' => null
            ];
        }

        // 7. Check Scheduling vs Immediate Dispatch
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
                'title' => 'Broadcast to ' . ucfirst($audience) . ' (' . count($recipientsData) . ' recipients)',
                'audience' => $audience,
                'selected_executives' => $selectedExecs,
                'custom_numbers' => $request->input('custom_numbers'),
                'message' => $message,
                'attachments' => $attachments,
                'recipients_data' => $recipientsData,
                'scheduled_at' => $scheduledAt,
                'status' => 'pending',
                'total_recipients' => count($recipientsData),
                'sent_count' => 0,
                'failed_count' => 0,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'scheduled' => true,
                'broadcast_id' => $broadcast->id,
                'message' => "Community Broadcast scheduled for " . $scheduledAt->format('d M Y, h:i A') . " UK Time (" . count($recipientsData) . " recipients).",
                'broadcast' => $broadcast
            ]);
        }

        // Immediate Dispatch: initialize record with all recipients pending so it can be dispatched via interactive micro-batches without server timeouts
        $selectedExecs = $request->input('selected_executives');
        if (is_string($selectedExecs)) {
            $selectedExecs = json_decode($selectedExecs, true) ?: [$selectedExecs];
        }

        $broadcast = WhatsAppScheduledBroadcast::create([
            'title' => 'Broadcast to ' . ucfirst($audience) . ' (' . count($recipientsData) . ' recipients)',
            'audience' => $audience,
            'selected_executives' => $selectedExecs,
            'custom_numbers' => $request->input('custom_numbers'),
            'message' => $message,
            'attachments' => $attachments,
            'recipients_data' => $recipientsData,
            'scheduled_at' => null,
            'executed_at' => Carbon::now(),
            'status' => 'pending',
            'total_recipients' => count($recipientsData),
            'sent_count' => 0,
            'failed_count' => 0,
            'created_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'scheduled' => false,
            'interactive' => true,
            'broadcast_id' => $broadcast->id,
            'total' => count($recipientsData),
            'title' => $broadcast->title,
            'message' => "Broadcast initialized for " . count($recipientsData) . " recipients. Launching live dispatch...",
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
     * Dispatch a single micro-batch (e.g. 3 recipients) of a broadcast.
     * Prevents HTTP timeouts, allows live progress tracking, and survives any interruption.
     */
    public function dispatchBatch(Request $request, $id)
    {
        $broadcast = WhatsAppScheduledBroadcast::findOrFail($id);
        $batchSize = max(1, min(10, (int) $request->input('batch_size', 3)));

        $recipients = $broadcast->recipients_data ?: [];
        if (empty($recipients)) {
            return response()->json([
                'success' => false,
                'message' => 'No recipients data found for this broadcast record.'
            ], 422);
        }

        // Find next chunk of pending recipients
        $pendingIndices = [];
        foreach ($recipients as $idx => $item) {
            if (($item['status'] ?? 'pending') === 'pending') {
                $pendingIndices[] = $idx;
                if (count($pendingIndices) >= $batchSize) {
                    break;
                }
            }
        }

        if (empty($pendingIndices)) {
            // All already finished
            $broadcast->status = ($broadcast->sent_count > 0) ? 'completed' : 'failed';
            $broadcast->save();

            return response()->json([
                'success' => true,
                'broadcast_id' => $broadcast->id,
                'batch_dispatched' => 0,
                'batch_results' => [],
                'total' => count($recipients),
                'sent' => $broadcast->sent_count,
                'failed' => $broadcast->failed_count,
                'remaining' => 0,
                'progress_percent' => 100,
                'is_completed' => true,
                'status' => $broadcast->status
            ]);
        }

        $batchResults = [];
        $now = Carbon::now();
        if (!$broadcast->executed_at) {
            $broadcast->executed_at = $now;
        }
        $broadcast->status = 'processing';

        $errorLogs = [];
        if (!empty($broadcast->error_log)) {
            $errorLogs = explode("\n", $broadcast->error_log);
        }

        foreach ($pendingIndices as $idx) {
            $r = $recipients[$idx];
            $phone = $r['phone'];
            $name = $r['name'] ?? 'Community Member';
            $membershipId = $r['membership_id'] ?? null;

            $res = OpenWaService::dispatchBroadcastBundle(
                $phone,
                $name,
                $broadcast->message,
                $broadcast->attachments ?: [],
                $membershipId
            );

            $isOk = is_array($res) ? ($res['success'] ?? false) : (bool)$res;
            $msgId = is_array($res) ? ($res['message_id'] ?? null) : null;

            if ($isOk) {
                $recipients[$idx]['status'] = 'sent';
                $recipients[$idx]['message_id'] = $msgId;
                $recipients[$idx]['sent_at'] = Carbon::now()->toDateTimeString();
                $recipients[$idx]['error'] = null;
                $batchResults[] = [
                    'phone' => $phone,
                    'formatted_phone' => OpenWaService::formatPhoneDisplay($phone),
                    'name' => $name,
                    'status' => 'sent',
                    'message_id' => $msgId
                ];
                $errorLogs[] = "[SUCCESS " . date('H:i:s') . "] Dispatched to {$name} (" . OpenWaService::formatPhoneDisplay($phone) . ")";
            } else {
                $recipients[$idx]['status'] = 'failed';
                $recipients[$idx]['message_id'] = null;
                $recipients[$idx]['sent_at'] = Carbon::now()->toDateTimeString();
                $recipients[$idx]['error'] = is_array($res) ? ($res['error'] ?? 'Dispatch failed') : 'Dispatch failed from gateway socket';
                $batchResults[] = [
                    'phone' => $phone,
                    'formatted_phone' => OpenWaService::formatPhoneDisplay($phone),
                    'name' => $name,
                    'status' => 'failed',
                    'message_id' => null
                ];
                $errorLogs[] = "[FAILED " . date('H:i:s') . "] Failed dispatch to {$name} (" . OpenWaService::formatPhoneDisplay($phone) . ")";
            }

            // Small 250ms gap between recipients to give WhatsApp socket room to breathe
            usleep(250000);
        }

        // Recompute real-time totals
        $sent = 0;
        $failed = 0;
        $remaining = 0;
        foreach ($recipients as $item) {
            $st = $item['status'] ?? 'pending';
            if ($st === 'sent') $sent++;
            elseif ($st === 'failed') $failed++;
            elseif ($st === 'pending') $remaining++;
        }

        $isCompleted = ($remaining === 0);
        if ($isCompleted) {
            $broadcast->status = ($sent > 0) ? 'completed' : 'failed';
        }

        $broadcast->sent_count = $sent;
        $broadcast->failed_count = $failed;
        $broadcast->recipients_data = $recipients;
        $broadcast->error_log = implode("\n", array_slice($errorLogs, -100));
        $broadcast->save();

        $totalCount = count($recipients);
        $percent = $totalCount > 0 ? round((($sent + $failed) / $totalCount) * 100, 1) : 100;

        return response()->json([
            'success' => true,
            'broadcast_id' => $broadcast->id,
            'batch_dispatched' => count($batchResults),
            'batch_results' => $batchResults,
            'total' => $totalCount,
            'sent' => $sent,
            'failed' => $failed,
            'remaining' => $remaining,
            'progress_percent' => $percent,
            'is_completed' => $isCompleted,
            'status' => $broadcast->status
        ]);
    }

    /**
     * Retrieve full broadcast recipient details & audit status for tracking modal
     */
    public function getBroadcastDetails($id)
    {
        $broadcast = WhatsAppScheduledBroadcast::findOrFail($id);
        $recipients = $broadcast->recipients_data ?: [];

        // Add human-friendly formatted phone to each recipient item
        $formattedList = [];
        $sentCount = 0;
        $failedCount = 0;
        $pendingCount = 0;

        foreach ($recipients as $item) {
            $st = $item['status'] ?? 'pending';
            if ($st === 'sent') $sentCount++;
            elseif ($st === 'failed') $failedCount++;
            else $pendingCount++;

            $formattedList[] = [
                'id' => $item['id'] ?? null,
                'name' => $item['name'] ?? 'Recipient',
                'phone' => $item['phone'] ?? '',
                'formatted_phone' => OpenWaService::formatPhoneDisplay($item['phone'] ?? ''),
                'status' => $st,
                'sent_at' => $item['sent_at'] ?? null,
                'message_id' => $item['message_id'] ?? null,
                'error' => $item['error'] ?? null
            ];
        }

        return response()->json([
            'success' => true,
            'broadcast' => [
                'id' => $broadcast->id,
                'title' => $broadcast->title,
                'audience' => $broadcast->audience,
                'status' => $broadcast->status,
                'total_recipients' => $broadcast->total_recipients,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'pending_count' => $pendingCount,
                'created_at' => $broadcast->created_at ? $broadcast->created_at->format('d M Y, h:i A') : '',
                'executed_at' => $broadcast->executed_at ? $broadcast->executed_at->format('d M Y, h:i A') : '',
                'scheduled_at' => $broadcast->scheduled_at ? $broadcast->scheduled_at->format('d M Y, h:i A') : null,
                'message' => $broadcast->message,
                'attachments' => $broadcast->attachments,
                'error_log' => $broadcast->error_log
            ],
            'recipients' => $formattedList
        ]);
    }

    /**
     * Resume an interrupted or pending broadcast
     */
    public function resumeBroadcast(Request $request, $id)
    {
        $broadcast = WhatsAppScheduledBroadcast::findOrFail($id);
        $broadcast->status = 'processing';
        if (!$broadcast->executed_at) {
            $broadcast->executed_at = Carbon::now();
        }
        $broadcast->save();

        $recipients = $broadcast->recipients_data ?: [];
        $pending = 0;
        foreach ($recipients as $item) {
            if (($item['status'] ?? 'pending') === 'pending') {
                $pending++;
            }
        }

        return response()->json([
            'success' => true,
            'broadcast_id' => $broadcast->id,
            'total' => count($recipients),
            'pending' => $pending,
            'sent' => $broadcast->sent_count,
            'failed' => $broadcast->failed_count,
            'message' => "Broadcast resumed. Ready to dispatch {$pending} remaining recipients."
        ]);
    }

    /**
     * Reset all failed recipients in a broadcast to pending so they can be retried
     */
    public function retryFailedRecipients(Request $request, $id)
    {
        $broadcast = WhatsAppScheduledBroadcast::findOrFail($id);
        $recipients = $broadcast->recipients_data ?: [];

        $resetCount = 0;
        foreach ($recipients as $idx => $item) {
            if (($item['status'] ?? '') === 'failed') {
                $recipients[$idx]['status'] = 'pending';
                $recipients[$idx]['error'] = null;
                $resetCount++;
            }
        }

        $broadcast->recipients_data = $recipients;
        $broadcast->failed_count = 0;
        $broadcast->status = 'processing';
        $broadcast->save();

        return response()->json([
            'success' => true,
            'reset_count' => $resetCount,
            'message' => "Reset {$resetCount} failed recipients to pending. You can now dispatch them."
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

    /**
     * Revoke all sent messages for a broadcast (Delete for Everyone on WhatsApp)
     */
    public function revokeBroadcast(Request $request, $id)
    {
        $broadcast = WhatsAppScheduledBroadcast::findOrFail($id);
        $recipients = $broadcast->recipients_data ?: [];

        $itemsToRevoke = [];
        $indicesToRevoke = [];
        foreach ($recipients as $idx => $r) {
            if (($r['status'] ?? '') === 'sent' && !empty($r['message_id']) && !empty($r['phone'])) {
                $itemsToRevoke[] = [
                    'to' => $r['phone'],
                    'messageId' => $r['message_id']
                ];
                $indicesToRevoke[] = $idx;
            }
        }

        if (empty($itemsToRevoke)) {
            return response()->json([
                'success' => false,
                'message' => 'No active sent messages with recorded WhatsApp Message IDs were found to revoke for this broadcast.'
            ], 422);
        }

        // Call bulk revoke on daemon
        $res = OpenWaService::revokeMessagesBulk($itemsToRevoke);

        // Mark successfully revoked recipients
        $revokedCount = 0;
        foreach ($indicesToRevoke as $idx) {
            $recipients[$idx]['status'] = 'revoked';
            $recipients[$idx]['revoked_at'] = Carbon::now()->toDateTimeString();
            $revokedCount++;
        }

        $broadcast->recipients_data = $recipients;
        $broadcast->status = 'revoked';
        $broadcast->save();

        return response()->json([
            'success' => true,
            'revoked_count' => $revokedCount,
            'message' => "Requested revocation for {$revokedCount} messages on WhatsApp.",
            'broadcast' => $broadcast
        ]);
    }

    /**
     * Revoke a single recipient's message from a broadcast
     */
    public function revokeRecipientMessage(Request $request, $id, $index)
    {
        $broadcast = WhatsAppScheduledBroadcast::findOrFail($id);
        $recipients = $broadcast->recipients_data ?: [];

        if (!isset($recipients[$index])) {
            return response()->json(['success' => false, 'message' => 'Recipient record not found.'], 404);
        }

        $r = $recipients[$index];
        $phone = $r['phone'] ?? null;
        $msgId = $r['message_id'] ?? null;

        if (!$phone || !$msgId) {
            return response()->json(['success' => false, 'message' => 'This message does not have a recorded WhatsApp Message ID to revoke.'], 422);
        }

        $res = OpenWaService::revokeMessage($phone, $msgId);

        if ($res['success']) {
            $recipients[$index]['status'] = 'revoked';
            $recipients[$index]['revoked_at'] = Carbon::now()->toDateTimeString();
            $broadcast->recipients_data = $recipients;
            $broadcast->save();

            return response()->json([
                'success' => true,
                'message' => "Message for {$r['name']} revoked successfully from WhatsApp."
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $res['message'] ?? 'Failed to revoke message on WhatsApp daemon.'
        ], 500);
    }

    /**
     * Revoke a direct message given phone and message ID
     */
    public function revokeDirectMessage(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message_id' => 'required|string'
        ]);

        $res = OpenWaService::revokeMessage($request->input('phone'), $request->input('message_id'));

        return response()->json($res, $res['success'] ? 200 : 500);
    }

    /**
     * Execute Daemon commands (status, start, restart, stop, logs) from web terminal
     */
    public function runDaemonCommand(Request $request)
    {
        $command = trim($request->input('command', 'status'));

        if (empty($command)) {
            $command = 'status';
        }

        // Alert via Telegram for start/stop/restart
        $action = strtolower(explode(' ', $command)[0]);
        if (in_array($action, ['start', 'stop', 'restart'])) {
            try {
                $admin = auth('admin')->user();
                \App\Services\TelegramService::sendMessage(
                    "🤖 <b>WhatsApp Daemon Action: " . strtoupper($action) . "</b>\n\n" .
                    "👤 <b>Admin:</b> " . htmlspecialchars($admin?->username ?? 'Admin') . "\n" .
                    "💻 <b>Command:</b> <code>" . htmlspecialchars($command) . "</code>\n" .
                    "🌐 <b>IP:</b> " . $request->ip()
                );
            } catch (\Throwable $e) {}
        }

        $res = OpenWaService::runDaemonCommand($command);

        return response()->json([
            'success' => $res['success'] ?? true,
            'output' => $res['output'] ?? 'Executed.',
            'command' => $command
        ]);
    }

    /**
     * Get list of WhatsApp templates (optionally filtered by category or search)
     */
    public function getTemplates(Request $request)
    {
        $query = WhatsAppTemplate::query();

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('body', 'like', "%{$s}%")
                    ->orWhere('header_content', 'like', "%{$s}%")
                    ->orWhere('category', 'like', "%{$s}%");
            });
        }

        $templates = $query->orderBy('is_system', 'desc')->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'templates' => $templates,
            'count' => $templates->count()
        ]);
    }

    /**
     * Get single WhatsApp template
     */
    public function getTemplate($id)
    {
        $template = WhatsAppTemplate::findOrFail($id);

        return response()->json([
            'success' => true,
            'template' => $template,
            'compiled_preview' => $template->compileFullWhatsAppMessage(),
            'rendered_body' => $template->renderBody()
        ]);
    }

    /**
     * Save or update WhatsApp template
     */
    public function saveTemplate(Request $request)
    {
        $request->validate([
            'id' => 'nullable|integer',
            'name' => 'required|string|max:150',
            'category' => 'required|string|max:50',
            'header_type' => 'required|string|in:none,text,image,document,video',
            'header_content' => 'nullable|string|max:500',
            'body' => 'required|string|max:4000',
            'footer' => 'nullable|string|max:255',
            'buttons' => 'nullable',
            'sample_data' => 'nullable'
        ]);

        $id = $request->input('id');
        $name = trim($request->input('name'));
        $category = strtolower(trim($request->input('category')));
        $headerType = $request->input('header_type', 'none');
        $headerContent = trim($request->input('header_content', ''));
        $body = trim($request->input('body'));
        $footer = trim($request->input('footer', ''));

        // Parse buttons
        $rawButtons = $request->input('buttons');
        $buttons = [];
        if (!empty($rawButtons)) {
            $buttons = is_array($rawButtons) ? $rawButtons : (json_decode($rawButtons, true) ?: []);
        }

        // Parse sample data
        $rawSample = $request->input('sample_data');
        $sampleData = [];
        if (!empty($rawSample)) {
            $sampleData = is_array($rawSample) ? $rawSample : (json_decode($rawSample, true) ?: []);
        }

        if ($id) {
            $template = WhatsAppTemplate::findOrFail($id);
            $template->name = $name;
            $template->category = $category;
            $template->header_type = $headerType;
            $template->header_content = $headerContent;
            $template->body = $body;
            $template->footer = $footer;
            $template->buttons = $buttons;
            if (!empty($sampleData)) {
                $template->sample_data = $sampleData;
            }
            $template->save();

            $msg = "Template '{$name}' updated successfully.";
        } else {
            $baseSlug = Str::slug($name);
            $slug = $baseSlug;
            $counter = 1;
            while (WhatsAppTemplate::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . (++$counter);
            }

            $template = WhatsAppTemplate::create([
                'name' => $name,
                'slug' => $slug,
                'category' => $category,
                'header_type' => $headerType,
                'header_content' => $headerContent,
                'body' => $body,
                'footer' => $footer,
                'buttons' => $buttons,
                'sample_data' => $sampleData,
                'is_system' => false,
                'created_by' => auth()->id()
            ]);

            $msg = "Template '{$name}' created successfully.";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'template' => $template,
            'compiled_preview' => $template->compileFullWhatsAppMessage(),
            'rendered_body' => $template->renderBody()
        ]);
    }

    /**
     * Delete a custom WhatsApp template
     */
    public function deleteTemplate($id)
    {
        $template = WhatsAppTemplate::findOrFail($id);
        $name = $template->name;

        // Prevent deleting core system templates unless forced
        if ($template->is_system) {
            return response()->json([
                'success' => false,
                'message' => "The template '{$name}' is a core system preset and cannot be deleted. You can clone and modify a custom copy instead."
            ], 403);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => "Template '{$name}' deleted successfully."
        ]);
    }

    /**
     * Clone an existing template
     */
    public function cloneTemplate($id)
    {
        $template = WhatsAppTemplate::findOrFail($id);

        $copyName = "Copy of " . $template->name;
        $baseSlug = Str::slug($copyName);
        $slug = $baseSlug . '-' . rand(100, 999);

        $clone = WhatsAppTemplate::create([
            'name' => $copyName,
            'slug' => $slug,
            'category' => $template->category,
            'header_type' => $template->header_type,
            'header_content' => $template->header_content,
            'body' => $template->body,
            'footer' => $template->footer,
            'buttons' => $template->buttons,
            'sample_data' => $template->sample_data,
            'is_system' => false,
            'created_by' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => "Template cloned as '{$copyName}'.",
            'template' => $clone
        ]);
    }

    /**
     * Live preview rendering of template with custom variables
     */
    public function previewTemplate(Request $request)
    {
        $body = $request->input('body', '');
        $headerType = $request->input('header_type', 'none');
        $headerContent = $request->input('header_content', '');
        $footer = $request->input('footer', '');
        $buttons = $request->input('buttons', []);
        if (is_string($buttons)) {
            $buttons = json_decode($buttons, true) ?: [];
        }

        $variables = $request->input('variables', []);
        if (is_string($variables)) {
            $variables = json_decode($variables, true) ?: [];
        }

        $mock = new WhatsAppTemplate([
            'header_type' => $headerType,
            'header_content' => $headerContent,
            'body' => $body,
            'footer' => $footer,
            'buttons' => $buttons,
            'sample_data' => $variables
        ]);

        return response()->json([
            'success' => true,
            'rendered_body' => $mock->renderBody($variables),
            'compiled_preview' => $mock->compileFullWhatsAppMessage($variables),
            'raw_preview' => $mock->compileFullWhatsAppMessage([])
        ]);
    }
}
