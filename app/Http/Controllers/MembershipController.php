<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Member;
use App\Models\MemberChild;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class MembershipController extends Controller
{
    public function index()
    {
        return view('membership');
    }

    public function viewIdCard($guid)
    {
        $member = Member::where('guid', $guid)->firstOrFail();
        return view('admin.members.print_card', compact('member'));
    }

    public function sendOtp(Request $request)
    {
        $membership_no = strtoupper(trim($request->input('membership_no')));
        if ($membership_no && !str_starts_with($membership_no, 'PMCC-')) {
            $membership_no = 'PMCC-' . $membership_no;
        }

        if (empty($membership_no) || $membership_no == 'PMCC-') {
            return response()->json(['success' => false, 'message' => 'Membership number is required']);
        }

        $member = Member::where('membership_id_assigned', $membership_no)
            ->whereIn('status', ['active', 'approved', 'expired'])
            ->first();

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Valid membership not found.']);
        }

        $email = $member->email;

        // Check for Placeholder Email
        if (str_ends_with(strtolower($email), '@placeholder.pmcc')) {
            return response()->json([
                'success' => false,
                'is_placeholder_email' => true,
                'message' => 'Your email address is currently a placeholder. Please contact Mr. Tom Jacob (Treasurer) at 07901296858 to update your email before renewing.'
            ]);
        }

        // Check if membership is still valid
        if ($member->expiry_date) {
            $expiry = strtotime($member->expiry_date);
            if ($expiry > time()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Renewal not required. Your membership is already valid until ' . date('d M Y', $expiry) . '.'
                ]);
            }
        }

        // Generate OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        Session::put('renewal_otp', $otp);
        Session::put('renewal_membership_no', $membership_no);
        Session::put('renewal_otp_time', time());

        try {
            Mail::to($email)->send(new OtpMail($member->full_name, $otp));

            // Mask email for UI privacy
            $parts = explode("@", $email);
            $user_part = $parts[0];
            $domain = $parts[1];
            $masked_user = (strlen($user_part) <= 3) ? $user_part . "***" : substr($user_part, 0, 2) . "***" . substr($user_part, -1);
            $masked = $masked_user . "@" . $domain;

            return response()->json(['success' => true, 'masked_email' => $masked]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Renewal Mail Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please check SMTP settings.',
                'debug' => $e->getMessage()
            ]);
        }
    }

    public function verifyOtp(Request $request)
    {
        $otp = $request->input('otp');
        $storedOtp = Session::get('renewal_otp');
        $storedNo = Session::get('renewal_membership_no');
        $storedTime = Session::get('renewal_otp_time');

        if (!$storedOtp || time() - $storedTime > 600) {
            return response()->json(['success' => false, 'message' => 'OTP expired or not found. Please resend.']);
        }

        if ($otp !== $storedOtp) {
            return response()->json(['success' => false, 'message' => 'Invalid verification code.']);
        }

        // Success - Fetch member data
        $id = strtoupper(trim($storedNo));
        if ($id && !str_starts_with($id, 'PMCC-')) {
            $id = 'PMCC-' . $id;
        }
        $member = Member::where('membership_id_assigned', $id)->first();
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Member data lost. Please restart.']);
        }

        $children = MemberChild::where('member_id', $member->id)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'email' => $member->email,
                'title' => $member->title,
                'full_name' => $member->full_name,
                'mobile_number' => $member->mobile_number,
                'dob' => $member->dob,
                'emergency_name' => $member->emergency_name,
                'emergency_mobile' => $member->emergency_mobile,
                'marital_status' => $member->marital_status,
                'spouse_name' => $member->spouse_name,
                'spouse_mobile' => $member->spouse_mobile,
                'spouse_dob' => $member->spouse_dob,
                'membership_type' => $member->membership_type,
                'post_code' => $member->post_code,
                'house_details' => $member->house_details,
                'children' => $children
            ]
        ]);
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'full_name' => 'required',
            'mobile_number' => 'required',
            'membership_type' => 'required',
            'member_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'family_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'consent' => 'accepted'
        ]);

        $is_renewal = !empty($request->input('prev_membership_no'));

        // 1. Photo Uploads
        $photo_filename = null;
        if ($request->hasFile('member_photo')) {
            $photo_filename = $request->file('member_photo')->store('photos', 'public');
        }

        $family_photo_filename = null;
        if ($request->hasFile('family_photo')) {
            $family_photo_filename = $request->file('family_photo')->store('photos', 'public');
        }

        if ($is_renewal) {
            $prev_no = strtoupper(trim($request->input('prev_membership_no')));
            if ($prev_no && !str_starts_with($prev_no, 'PMCC-')) {
                $prev_no = 'PMCC-' . $prev_no;
            }
            $existing_member = Member::where('membership_id_assigned', $prev_no)->firstOrFail();

            // Create Renewal Request
            $renewal = \App\Models\RenewalRequest::create([
                'created_at' => now(),
                'member_id' => $existing_member->id,
                'title' => $request->input('title'),
                'full_name' => $request->input('full_name'),
                'email' => $request->input('email'),
                'mobile_number' => $request->input('mobile_number'),
                'dob' => $request->input('dob'),
                'marital_status' => $request->input('marital_status'),
                'membership_type' => $request->input('membership_type'),
                'spouse_name' => $request->input('spouse_name'),
                'spouse_mobile' => $request->input('spouse_mobile'),
                'spouse_dob' => $request->input('spouse_dob'),
                'post_code' => $request->input('post_code'),
                'house_details' => $request->input('house_details'),
                'emergency_name' => $request->input('emergency_name'),
                'emergency_mobile' => $request->input('emergency_mobile'),
                'photo' => $photo_filename,
                'family_photo' => $family_photo_filename,
                'payment_date' => $request->input('payment_date'),
                'bank_account_holder' => $request->input('bank_account_holder'),
                'status' => 'pending',
                'request_year' => 2026
            ]);

            // Save Children
            if ($request->has('child_name')) {
                foreach ($request->input('child_name') as $i => $name) {
                    if (!empty($name)) {
                        \App\Models\RenewalChild::create([
                            'renewal_id' => $renewal->id,
                            'child_name' => $name,
                            'sex' => $request->input('child_sex')[$i] ?? 'Male',
                            'dob' => $request->input('child_dob')[$i] ?? null
                        ]);
                    }
                }
            }
        } else {
            // New Registration
            $member = Member::create([
                'guid' => (string) \Illuminate\Support\Str::uuid(),
                'email' => $request->input('email'),
                'title' => $request->input('title'),
                'full_name' => $request->input('full_name'),
                'dob' => $request->input('dob'),
                'marital_status' => $request->input('marital_status'),
                'spouse_name' => $request->input('spouse_name'),
                'spouse_mobile' => $request->input('spouse_mobile'),
                'spouse_dob' => $request->input('spouse_dob'),
                'mobile_number' => $request->input('mobile_number'),
                'emergency_name' => $request->input('emergency_name'),
                'emergency_mobile' => $request->input('emergency_mobile'),
                'post_code' => $request->input('post_code'),
                'house_details' => $request->input('house_details'),
                'prev_membership_no' => $request->input('prev_membership_no'),
                'membership_type' => $request->input('membership_type'),
                'payment_date' => $request->input('payment_date'),
                'bank_account_holder' => $request->input('bank_account_holder'),
                'status' => 'pending',
                'consent_given' => 1,
                'photo' => $photo_filename,
                'family_photo' => $family_photo_filename,
            ]);

            // Save Children
            if ($request->has('child_name')) {
                foreach ($request->input('child_name') as $i => $name) {
                    if (!empty($name)) {
                        $dob = $request->input('child_dob')[$i] ?? null;
                        $age = $dob ? now()->diff(\Illuminate\Support\Carbon::parse($dob))->y : 0;
                        MemberChild::create([
                            'member_id' => $member->id,
                            'child_name' => $name,
                            'sex' => $request->input('child_sex')[$i] ?? 'Male',
                            'dob' => $dob,
                            'age' => $age
                        ]);
                    }
                }
            }
        }

        // Send Email (Mailable implementation)
        try {
            \App\Models\ActivityLog::create([
                'user_type' => 'guest',
                'action' => 'membership_applied',
                'details' => "Applied: {$request->input('full_name')} ({$request->input('email')})",
                'ip_address' => $request->ip()
            ]);

            \Illuminate\Support\Facades\Mail::to($request->input('email'))
                ->send(new \App\Mail\MembershipSubmittedMail($request->input('full_name')));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Email failed for {$request->input('email')}: " . $e->getMessage());
        }

        // Send Telegram Notification
        try {
            if ($is_renewal) {
                \App\Services\TelegramService::sendMessage(
                    "🔄 <b>New Membership Renewal Request</b>\n\n" .
                    "👤 <b>Name:</b> " . $request->input('full_name') . "\n" .
                    "📧 <b>Email:</b> " . $request->input('email') . "\n" .
                    "📱 <b>Mobile:</b> " . $request->input('mobile_number') . "\n" .
                    "💳 <b>Membership Type:</b> " . $request->input('membership_type')
                );
            } else {
                \App\Services\TelegramService::sendMessage(
                    "📝 <b>New Membership Application</b>\n\n" .
                    "👤 <b>Name:</b> " . $request->input('full_name') . "\n" .
                    "📧 <b>Email:</b> " . $request->input('email') . "\n" .
                    "📱 <b>Mobile:</b> " . $request->input('mobile_number') . "\n" .
                    "💳 <b>Membership Type:</b> " . $request->input('membership_type')
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Telegram failed for membership: " . $e->getMessage());
        }

        return redirect()->route('membership')->with('status', 'success');
    }
}
