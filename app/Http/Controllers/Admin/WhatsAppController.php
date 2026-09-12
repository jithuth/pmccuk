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

        $counts = [
            'members' => \App\Models\Member::where('status', 'active')->whereNotNull('mobile_number')->count(),
            'attendees' => \App\Models\EventBooking::where('booking_status', 'approved')->whereNotNull('phone')->count(),
            'students' => \App\Models\StudentRequest::whereNotNull('phone')->count(),
        ];

        return view('admin.whatsapp.index', compact('status', 'settings', 'counts'));
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
            'audience' => 'required|string|in:members,attendees,students,custom',
            'message' => 'required|string|max:2000',
            'custom_numbers' => 'nullable|string'
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
}
