<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberConsent;
use App\Models\PrivacyNotice;
use App\Models\PrivacyRequest;
use App\Models\PrivacyRequestAction;
use App\Models\PrivacyRequestDocument;
use App\Models\PrivacyRequestItem;
use App\Models\PrivacyRequestStatusHistory;
use App\Services\SecurityAuditService;
use App\Services\StatutoryDeadlineCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemberPrivacyController extends Controller
{
    private function getAuthenticatedMember(): Member
    {
        /** @var Member $member */
        $member = Auth::guard('member')->user();
        if (!$member) {
            abort(401, 'Unauthenticated member access.');
        }
        return $member;
    }

    public function index()
    {
        $member = $this->getAuthenticatedMember();
        $member->load(['children', 'consents', 'payments', 'privacyRequests.documents']);

        $eventBookings = \App\Models\EventBooking::where(function($q) use ($member) {
            if ($member->membership_id_assigned) {
                $q->where('membership_no', $member->membership_id_assigned);
            }
            if ($member->email) {
                $q->orWhere('email', $member->email);
            }
            if ($member->mobile_number) {
                $q->orWhere('phone', $member->mobile_number);
            }
        })
        ->with('event')
        ->orderBy('id', 'desc')
        ->get();

        $activeNotice = PrivacyNotice::where('is_active', true)->latest('published_at')->first();

        SecurityAuditService::log(
            'privacy_center_accessed',
            'Member',
            $member->id,
            'Member accessed personal Privacy Center'
        );

        return view('member.privacy.index', compact('member', 'activeNotice', 'eventBookings'));
    }

    public function submitSar(Request $request)
    {
        $member = $this->getAuthenticatedMember();

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $reference = 'SAR-' . date('Y') . '-' . strtoupper(Str::random(6));
        $statutoryDeadline = StatutoryDeadlineCalculator::calculateDeadline(now());

        $privacyRequest = PrivacyRequest::create([
            'reference_number'            => $reference,
            'member_id'                   => $member->id,
            'requester_name'              => $member->full_name,
            'requester_email'             => $member->email,
            'requester_phone'             => $member->mobile_number,
            'request_type'                => 'sar',
            'details'                     => $request->input('notes') ?: 'Subject Access Request for complete personal data, family records, registration details, and payment histories.',
            'identity_verified'           => true, // Authenticated session
            'identity_verified_at'        => now(),
            'identity_verification_method'=> 'Authenticated Member Session (ID + Credentials/OTP)',
            'received_at'                 => now(),
            'statutory_deadline'          => $statutoryDeadline->toDateString(),
            'status'                      => 'submitted',
            'correlation_id'              => SecurityAuditService::getCorrelationId(),
        ]);

        PrivacyRequestStatusHistory::create([
            'privacy_request_id' => $privacyRequest->id,
            'from_status'        => null,
            'to_status'          => 'submitted',
            'reason'             => 'Submitted by authenticated member via Privacy Center',
            'created_at'         => now(),
        ]);

        PrivacyRequestAction::create([
            'privacy_request_id' => $privacyRequest->id,
            'action_type'        => 'created',
            'description'        => 'Subject Access Request registered. Statutory response deadline: ' . $statutoryDeadline->format('d M Y') . '.',
            'created_at'         => now(),
        ]);

        SecurityAuditService::log(
            'sar_submitted',
            'PrivacyRequest',
            $privacyRequest->id,
            "Subject Access Request {$reference} created by member",
            'success',
            ['reference' => $reference, 'statutory_deadline' => $statutoryDeadline->toDateString()]
        );

        return redirect()->route('member.privacy.index', ['tab' => 'history'])
            ->with('success', "Your Subject Access Request ({$reference}) has been registered. Under UK GDPR, our Privacy Officer will review and prepare your secure download package by {$statutoryDeadline->format('d M Y')}.");
    }

    public function submitRectification(Request $request)
    {
        $member = $this->getAuthenticatedMember();

        $request->validate([
            'field_category' => 'required|string',
            'field_name'     => 'required|string',
            'proposed_value' => 'required|string|max:500',
            'justification'  => 'required|string|max:1000',
        ]);

        $reference = 'REC-' . date('Y') . '-' . strtoupper(Str::random(6));
        $statutoryDeadline = StatutoryDeadlineCalculator::calculateDeadline(now());

        $privacyRequest = PrivacyRequest::create([
            'reference_number'            => $reference,
            'member_id'                   => $member->id,
            'requester_name'              => $member->full_name,
            'requester_email'             => $member->email,
            'requester_phone'             => $member->mobile_number,
            'request_type'                => 'rectification',
            'details'                     => "Rectification of {$request->field_name} in category {$request->field_category}. Reason: {$request->justification}",
            'identity_verified'           => true,
            'identity_verified_at'        => now(),
            'identity_verification_method'=> 'Authenticated Member Session',
            'received_at'                 => now(),
            'statutory_deadline'          => $statutoryDeadline->toDateString(),
            'status'                      => 'submitted',
            'correlation_id'              => SecurityAuditService::getCorrelationId(),
        ]);

        // Get current value
        $currentValue = $member->{$request->field_name} ?? 'N/A';

        PrivacyRequestItem::create([
            'privacy_request_id' => $privacyRequest->id,
            'category'           => $request->field_category,
            'field_name'         => $request->field_name,
            'current_value'      => is_scalar($currentValue) ? (string) $currentValue : json_encode($currentValue),
            'proposed_value'     => $request->proposed_value,
            'status'             => 'pending',
            'notes'              => $request->justification,
        ]);

        PrivacyRequestStatusHistory::create([
            'privacy_request_id' => $privacyRequest->id,
            'from_status'        => null,
            'to_status'          => 'submitted',
            'reason'             => 'Rectification submitted by member',
            'created_at'         => now(),
        ]);

        SecurityAuditService::log(
            'rectification_submitted',
            'PrivacyRequest',
            $privacyRequest->id,
            "Rectification request {$reference} for field {$request->field_name}",
            'success'
        );

        return redirect()->route('member.privacy.index', ['tab' => 'history'])
            ->with('success', "Rectification request ({$reference}) submitted. A Data Governance Officer will review your amendment by {$statutoryDeadline->format('d M Y')}.");
    }

    public function submitErasure(Request $request)
    {
        $member = $this->getAuthenticatedMember();

        $request->validate([
            'erasure_scope'       => 'required|in:specific_fields,past_events,all_discretionary',
            'erasure_reason'      => 'required|string|max:1000',
            'confirm_non_cancel'  => 'accepted', // Explicit user agreement that erasure does NOT cancel membership
        ]);

        $reference = 'ERA-' . date('Y') . '-' . strtoupper(Str::random(6));
        $statutoryDeadline = StatutoryDeadlineCalculator::calculateDeadline(now());

        $privacyRequest = PrivacyRequest::create([
            'reference_number'            => $reference,
            'member_id'                   => $member->id,
            'requester_name'              => $member->full_name,
            'requester_email'             => $member->email,
            'requester_phone'             => $member->mobile_number,
            'request_type'                => 'erasure',
            'details'                     => "Scope: {$request->erasure_scope}. Reason: {$request->erasure_reason}. Note: Member acknowledged that erasure does not cancel active membership.",
            'identity_verified'           => true,
            'identity_verified_at'        => now(),
            'identity_verification_method'=> 'Authenticated Member Session',
            'received_at'                 => now(),
            'statutory_deadline'          => $statutoryDeadline->toDateString(),
            'status'                      => 'submitted',
            'correlation_id'              => SecurityAuditService::getCorrelationId(),
        ]);

        PrivacyRequestStatusHistory::create([
            'privacy_request_id' => $privacyRequest->id,
            'from_status'        => null,
            'to_status'          => 'submitted',
            'reason'             => 'Right to Erasure submitted by member',
            'created_at'         => now(),
        ]);

        SecurityAuditService::log(
            'erasure_request_submitted',
            'PrivacyRequest',
            $privacyRequest->id,
            "Erasure request {$reference} registered by member",
            'success',
            ['scope' => $request->erasure_scope]
        );

        return redirect()->route('member.privacy.index', ['tab' => 'history'])
            ->with('success', "Your Erasure Request ({$reference}) has been logged. Independent Privacy Officer approval is required before data disposal is executed. Note: Valid legal obligations (such as HMRC financial records) cannot be deleted.");
    }

    public function updateConsents(Request $request)
    {
        $member = $this->getAuthenticatedMember();

        $consentKeys = [
            'marketing_emails'     => 'Optional promotional announcements and external cultural partner updates.',
            'whatsapp_broadcasts'  => 'Community updates and emergency announcements via WhatsApp broadcasts.',
            'photo_publication'    => 'Publishing cultural festival event photos in the public gallery.',
        ];

        foreach ($consentKeys as $key => $desc) {
            $granted = $request->boolean($key);
            $existing = MemberConsent::where('member_id', $member->id)->where('consent_key', $key)->first();

            if ($existing) {
                if ($existing->granted !== $granted) {
                    $existing->update([
                        'granted'    => $granted,
                        'granted_at' => $granted ? now() : $existing->granted_at,
                        'revoked_at' => !$granted ? now() : null,
                        'ip_address' => $request->ip(),
                    ]);
                }
            } else {
                MemberConsent::create([
                    'member_id'   => $member->id,
                    'consent_key' => $key,
                    'lawful_basis'=> 'consent',
                    'granted'     => $granted,
                    'granted_at'  => $granted ? now() : null,
                    'revoked_at'  => !$granted ? now() : null,
                    'ip_address'  => $request->ip(),
                ]);
            }
        }

        SecurityAuditService::log(
            'consents_updated',
            'Member',
            $member->id,
            'Member updated communication and privacy consent preferences',
            'success'
        );

        return redirect()->route('member.privacy.index', ['tab' => 'consents'])
            ->with('success', 'Your privacy and communication preferences have been updated.');
    }

    public function downloadDocument(string $token)
    {
        $member = $this->getAuthenticatedMember();

        $document = PrivacyRequestDocument::where('download_token', $token)->firstOrFail();

        // Enforce authorization: document must belong to a request submitted by this member
        if ($document->request->member_id !== $member->id) {
            SecurityAuditService::log('unauthorized_sar_download_attempt', 'PrivacyRequestDocument', $document->id, 'IDOR download attempt by different member', 'denied');
            abort(403, 'Unauthorized access to this privacy document.');
        }

        // Verify token expiry
        if (!$document->isDownloadValid()) {
            SecurityAuditService::log('sar_download_expired', 'PrivacyRequestDocument', $document->id, 'Attempted to access expired download token', 'denied');
            return redirect()->route('member.privacy.index', ['tab' => 'history'])
                ->with('error', 'This download link has expired for security reasons. Please contact the Privacy Officer to refresh your download link.');
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Export package file not found on server.');
        }

        $document->update([
            'downloaded_at' => now(),
            'downloaded_ip' => request()->ip(),
        ]);

        SecurityAuditService::log(
            'sar_document_downloaded',
            'PrivacyRequestDocument',
            $document->id,
            "Member downloaded SAR package: {$document->file_name}",
            'success'
        );

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    public function viewRegistrationRecord()
    {
        $member = $this->getAuthenticatedMember();

        SecurityAuditService::log(
            'registration_record_viewed',
            'Member',
            $member->id,
            'Member viewed verified membership registration record'
        );

        return view('member.privacy.registration_record', compact('member'));
    }
}
