<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\DataRetentionLog;
use App\Models\DataRetentionPolicy;
use App\Models\Member;
use App\Models\PrivacyNotice;
use App\Models\PrivacyRequest;
use App\Models\PrivacyRequestAction;
use App\Models\PrivacyRequestApproval;
use App\Models\PrivacyRequestStatusHistory;
use App\Models\PrivilegedAccessGrant;
use App\Models\Role;
use App\Models\SecurityAuditLog;
use App\Models\SecurityIncident;
use App\Models\SecurityIncidentTimeline;
use App\Services\MemberDataExportService;
use App\Services\SecurityAuditService;
use App\Services\StatutoryDeadlineCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PrivacyOfficerController extends Controller
{
    private function getActor(): Admin
    {
        /** @var Admin $actor */
        $actor = Auth::guard('admin')->user();
        if (!$actor) {
            abort(401, 'Unauthenticated administrator.');
        }

        // Developer isolation check
        if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
            abort(403, 'Developers do not have default access to the Privacy & Governance dashboard without an active, approved Break-Glass emergency grant.');
        }

        return $actor;
    }

    public function dashboard()
    {
        $actor = $this->getActor();

        $allRequests = PrivacyRequest::all();
        $openRequests = $allRequests->filter(fn($r) => !in_array($r->status, ['completed', 'partially_fulfilled', 'refused', 'withdrawn']));

        $kpis = [
            'total_open'            => $openRequests->count(),
            'overdue'               => $openRequests->filter(fn($r) => $r->isOverdue())->count(),
            'due_this_week'         => $openRequests->filter(fn($r) => $r->getDaysRemaining() >= 0 && $r->getDaysRemaining() <= 7)->count(),
            'pending_identity'      => $openRequests->where('identity_verified', false)->count(),
            'pending_approval'      => PrivacyRequestApproval::where('status', 'pending')->count(),
            'completed'             => $allRequests->whereIn('status', ['completed', 'partially_fulfilled'])->count(),
            'active_incidents'      => SecurityIncident::whereNotIn('status', ['closed'])->count(),
            'active_break_glass'    => PrivilegedAccessGrant::where('status', 'active')->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count(),
        ];

        $recentRequests = PrivacyRequest::with(['member', 'assignee'])
            ->orderBy('received_at', 'desc')
            ->limit(8)
            ->get();

        $activeIncidents = SecurityIncident::whereNotIn('status', ['closed'])
            ->orderBy('detected_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.privacy.dashboard', compact('kpis', 'recentRequests', 'activeIncidents'));
    }

    public function requestsIndex(Request $request)
    {
        $actor = $this->getActor();
        $filterType = $request->input('type');
        $filterStatus = $request->input('status');
        $search = $request->input('search');

        $query = PrivacyRequest::with(['member', 'assignee', 'approvals']);

        if ($filterType && $filterType !== 'all') {
            $query->where('request_type', $filterType);
        }

        if ($filterStatus && $filterStatus !== 'all') {
            if ($filterStatus === 'open') {
                $query->whereNotIn('status', ['completed', 'partially_fulfilled', 'refused', 'withdrawn']);
            } else {
                $query->where('status', $filterStatus);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'LIKE', "%{$search}%")
                  ->orWhere('requester_name', 'LIKE', "%{$search}%")
                  ->orWhere('requester_email', 'LIKE', "%{$search}%");
            });
        }

        $requests = $query->orderBy('statutory_deadline', 'asc')->paginate(15)->withQueryString();

        return view('admin.privacy.requests.index', compact('requests', 'filterType', 'filterStatus', 'search'));
    }

    public function showRequest($id)
    {
        $actor = $this->getActor();
        $privacyRequest = PrivacyRequest::with([
            'member.children',
            'member.payments',
            'items',
            'actions.actor',
            'approvals.approver',
            'documents',
            'statusHistory.changer'
        ])->findOrFail($id);

        $admins = Admin::orderBy('username')->get();

        SecurityAuditService::log(
            'privacy_request_inspected',
            'PrivacyRequest',
            $privacyRequest->id,
            "Officer inspected Privacy Request {$privacyRequest->reference_number}"
        );

        return view('admin.privacy.requests.show', compact('privacyRequest', 'admins'));
    }

    public function verifyIdentity(Request $request, $id)
    {
        $actor = $this->getActor();
        $privacyRequest = PrivacyRequest::findOrFail($id);

        $request->validate([
            'method' => 'required|string|max:100',
        ]);

        $privacyRequest->update([
            'identity_verified'            => true,
            'identity_verified_at'         => now(),
            'identity_verification_method' => $request->method,
        ]);

        PrivacyRequestAction::create([
            'privacy_request_id' => $privacyRequest->id,
            'admin_id'           => $actor->id,
            'action_type'        => 'identity_verified',
            'description'        => "Identity verified via: {$request->method} by {$actor->username}",
            'created_at'         => now(),
        ]);

        SecurityAuditService::log(
            'privacy_identity_verified',
            'PrivacyRequest',
            $privacyRequest->id,
            "Identity verified for {$privacyRequest->reference_number} by {$actor->username}"
        );

        return back()->with('success', 'Requester identity has been formally verified.');
    }

    public function assignReviewer(Request $request, $id)
    {
        $actor = $this->getActor();
        $privacyRequest = PrivacyRequest::findOrFail($id);

        $request->validate([
            'assigned_to' => 'required|exists:admins,id',
        ]);

        $assignee = Admin::findOrFail($request->assigned_to);

        $privacyRequest->update([
            'assigned_to' => $assignee->id,
        ]);

        PrivacyRequestAction::create([
            'privacy_request_id' => $privacyRequest->id,
            'admin_id'           => $actor->id,
            'action_type'        => 'reviewer_assigned',
            'description'        => "Reviewer assigned to {$assignee->username} by {$actor->username}",
            'created_at'         => now(),
        ]);

        return back()->with('success', "Request assigned to {$assignee->username}.");
    }

    public function extendDeadline(Request $request, $id)
    {
        $actor = $this->getActor();
        $privacyRequest = PrivacyRequest::findOrFail($id);

        $request->validate([
            'extension_months' => 'required|integer|min:1|max:2',
            'extension_reason' => 'required|string|min:10|max:1000',
        ]);

        $receivedDate = Carbon::parse($privacyRequest->received_at);
        $newDeadline = StatutoryDeadlineCalculator::calculateDeadline($receivedDate, (int) $request->extension_months);

        $privacyRequest->update([
            'extended_deadline' => $newDeadline->toDateString(),
            'extension_reason'  => $request->extension_reason,
        ]);

        PrivacyRequestAction::create([
            'privacy_request_id' => $privacyRequest->id,
            'admin_id'           => $actor->id,
            'action_type'        => 'deadline_extended',
            'description'        => "Statutory deadline extended by {$request->extension_months} month(s) to {$newDeadline->format('d M Y')}. Justification: {$request->extension_reason}",
            'created_at'         => now(),
        ]);

        SecurityAuditService::log(
            'statutory_deadline_extended',
            'PrivacyRequest',
            $privacyRequest->id,
            "Deadline extended for {$privacyRequest->reference_number} by {$actor->username}",
            'success',
            ['extended_deadline' => $newDeadline->toDateString(), 'reason' => $request->extension_reason]
        );

        return back()->with('success', "Statutory response deadline successfully extended to {$newDeadline->format('d M Y')}.");
    }

    public function addApproval(Request $request, $id)
    {
        $actor = $this->getActor();
        $privacyRequest = PrivacyRequest::findOrFail($id);

        $request->validate([
            'approval_type' => 'required|string',
            'decision'      => 'required|in:approved,rejected',
            'comments'      => 'nullable|string|max:1000',
        ]);

        // Anti-Self-Approval check
        if ($actor->email && strtolower($actor->email) === strtolower($privacyRequest->requester_email)) {
            return back()->with('error', 'Conflict of Interest: You cannot review or approve your own personal request.');
        }

        $approval = PrivacyRequestApproval::create([
            'privacy_request_id' => $privacyRequest->id,
            'approver_id'        => $actor->id,
            'role_at_approval'   => $actor->role ?: 'Administrator',
            'approval_type'      => $request->approval_type,
            'status'             => $request->decision,
            'comments'           => $request->comments,
            'decided_at'         => now(),
        ]);

        PrivacyRequestAction::create([
            'privacy_request_id' => $privacyRequest->id,
            'admin_id'           => $actor->id,
            'action_type'        => 'approval_decision',
            'description'        => "Independent approval {$request->approval_type} decided: " . strtoupper($request->decision) . " by {$actor->username}",
            'created_at'         => now(),
        ]);

        SecurityAuditService::log(
            'privacy_approval_decided',
            'PrivacyRequestApproval',
            $approval->id,
            "Approval decision {$request->decision} recorded by {$actor->username}",
            'success'
        );

        return back()->with('success', "Independent approval decision recorded ({$request->decision}).");
    }

    public function generateSarPackage($id)
    {
        $actor = $this->getActor();
        $privacyRequest = PrivacyRequest::with('member')->findOrFail($id);

        if (!$privacyRequest->member) {
            return back()->with('error', 'Cannot compile SAR package: no member profile is linked to this request.');
        }

        $docs = MemberDataExportService::generateExportDocuments($privacyRequest);

        $privacyRequest->update(['status' => 'fulfilment']);

        PrivacyRequestAction::create([
            'privacy_request_id' => $privacyRequest->id,
            'admin_id'           => $actor->id,
            'action_type'        => 'sar_package_generated',
            'description'        => "Compiled Subject Access disclosure package (JSON & CSV formats) with 7-day secure expiring tokens.",
            'created_at'         => now(),
        ]);

        SecurityAuditService::log(
            'sar_package_generated',
            'PrivacyRequest',
            $privacyRequest->id,
            "Compiled SAR documents for {$privacyRequest->reference_number}"
        );

        return back()->with('success', "Generated " . count($docs) . " secure Subject Access document(s). Available for download by the authenticated member.");
    }

    public function fulfilRequest(Request $request, $id)
    {
        $actor = $this->getActor();
        $privacyRequest = PrivacyRequest::findOrFail($id);

        $request->validate([
            'outcome'           => 'required|in:completed,partially_fulfilled',
            'decision_reasoning'=> 'required|string|max:2000',
        ]);

        $prevStatus = $privacyRequest->status;

        $privacyRequest->update([
            'status'            => $request->outcome,
            'decision'          => $request->outcome === 'completed' ? 'approved' : 'partially_approved',
            'decision_reasoning'=> $request->decision_reasoning,
            'completed_at'      => now(),
        ]);

        PrivacyRequestStatusHistory::create([
            'privacy_request_id' => $privacyRequest->id,
            'from_status'        => $prevStatus,
            'to_status'          => $request->outcome,
            'changed_by'         => $actor->id,
            'reason'             => $request->decision_reasoning,
            'created_at'         => now(),
        ]);

        SecurityAuditService::log(
            'privacy_request_fulfilled',
            'PrivacyRequest',
            $privacyRequest->id,
            "Privacy request {$privacyRequest->reference_number} fulfilled with status {$request->outcome}"
        );

        return back()->with('success', "Privacy request marked as {$request->outcome}. Member can view final status and documents in their Privacy Center.");
    }

    public function refuseRequest(Request $request, $id)
    {
        $actor = $this->getActor();
        $privacyRequest = PrivacyRequest::findOrFail($id);

        $request->validate([
            'decision_reasoning'     => 'required|string|min:15|max:2000',
            'applicable_exemptions'  => 'nullable|string|max:500',
        ]);

        $prevStatus = $privacyRequest->status;

        $privacyRequest->update([
            'status'               => 'refused',
            'decision'             => 'refused',
            'decision_reasoning'   => $request->decision_reasoning,
            'applicable_exemptions'=> $request->applicable_exemptions,
            'completed_at'         => now(),
        ]);

        PrivacyRequestStatusHistory::create([
            'privacy_request_id' => $privacyRequest->id,
            'from_status'        => $prevStatus,
            'to_status'          => 'refused',
            'changed_by'         => $actor->id,
            'reason'             => $request->decision_reasoning,
            'created_at'         => now(),
        ]);

        SecurityAuditService::log(
            'privacy_request_refused',
            'PrivacyRequest',
            $privacyRequest->id,
            "Request {$privacyRequest->reference_number} refused by {$actor->username}. Justification: {$request->decision_reasoning}",
            'success',
            ['exemptions' => $request->applicable_exemptions]
        );

        return back()->with('success', "Request formal refusal recorded with legal reasoning.");
    }

    // ── SECURITY INCIDENTS REGISTER ──
    public function incidentsIndex()
    {
        $actor = $this->getActor();
        $incidents = SecurityIncident::with(['reporter', 'investigator'])
            ->orderBy('detected_at', 'desc')
            ->paginate(15);

        return view('admin.privacy.incidents.index', compact('incidents'));
    }

    public function showIncident($id)
    {
        $actor = $this->getActor();
        $incident = SecurityIncident::with(['reporter', 'investigator', 'timeline.recorder'])->findOrFail($id);
        $admins = Admin::orderBy('username')->get();

        return view('admin.privacy.incidents.show', compact('incident', 'admins'));
    }

    public function storeIncident(Request $request)
    {
        $actor = $this->getActor();

        $request->validate([
            'title'                      => 'required|string|max:200',
            'severity'                   => 'required|in:low,medium,high,critical',
            'detected_at'                => 'required|date',
            'estimated_affected_members' => 'nullable|integer|min:0',
            'containment_actions'        => 'nullable|string|max:2000',
            'ico_notifiable'             => 'boolean',
            'ico_assessment_reasoning'   => 'nullable|string|max:2000',
        ]);

        $reference = 'INC-' . date('Y') . '-' . strtoupper(Str::random(6));

        $incident = SecurityIncident::create([
            'reference_number'           => $reference,
            'title'                      => $request->title,
            'severity'                   => $request->severity,
            'status'                     => 'open',
            'estimated_affected_members' => (int) $request->estimated_affected_members,
            'detected_at'                => Carbon::parse($request->detected_at),
            'reported_by'                => $actor->id,
            'lead_investigator'          => $actor->id,
            'containment_actions'        => $request->containment_actions,
            'ico_notifiable'             => $request->boolean('ico_notifiable'),
            'ico_assessment_reasoning'   => $request->ico_assessment_reasoning,
        ]);

        SecurityIncidentTimeline::create([
            'security_incident_id' => $incident->id,
            'recorded_by'          => $actor->id,
            'event_type'           => 'detection',
            'summary'              => "Incident reported by {$actor->username}. Severity: " . strtoupper($request->severity),
            'event_timestamp'      => Carbon::parse($request->detected_at),
            'created_at'           => now(),
        ]);

        SecurityAuditService::log(
            'security_incident_created',
            'SecurityIncident',
            $incident->id,
            "Security incident {$reference} opened by {$actor->username} with severity {$request->severity}"
        );

        return redirect()->route('admin.privacy.incidents.show', $incident->id)
            ->with('success', "Security incident {$reference} registered.");
    }

    public function addIncidentTimeline(Request $request, $id)
    {
        $actor = $this->getActor();
        $incident = SecurityIncident::findOrFail($id);

        $request->validate([
            'event_type' => 'required|in:detection,containment,investigation,notification,remediation',
            'summary'    => 'required|string|max:1000',
        ]);

        SecurityIncidentTimeline::create([
            'security_incident_id' => $incident->id,
            'recorded_by'          => $actor->id,
            'event_type'           => $request->event_type,
            'summary'              => $request->summary,
            'event_timestamp'      => now(),
            'created_at'           => now(),
        ]);

        return back()->with('success', 'Incident timeline entry logged.');
    }

    public function updateIncident(Request $request, $id)
    {
        $actor = $this->getActor();
        $incident = SecurityIncident::findOrFail($id);

        $request->validate([
            'status'                   => 'required|in:open,investigating,contained,remediated,closed',
            'lead_investigator'        => 'nullable|exists:admins,id',
            'ico_notifiable'           => 'boolean',
            'ico_assessment_reasoning' => 'nullable|string|max:2000',
            'ico_reference_number'     => 'nullable|string|max:80',
            'containment_actions'      => 'nullable|string',
            'remediation_steps'        => 'nullable|string',
        ]);

        $data = $request->only([
            'status', 'lead_investigator', 'ico_assessment_reasoning',
            'ico_reference_number', 'containment_actions', 'remediation_steps'
        ]);
        $data['ico_notifiable'] = $request->boolean('ico_notifiable');

        if ($request->boolean('ico_notifiable') && $request->filled('ico_reference_number') && !$incident->ico_notified_at) {
            $data['ico_notified_at'] = now();
        }

        if ($request->status === 'closed' && !$incident->closed_at) {
            $data['closed_at'] = now();
        }

        $incident->update($data);

        SecurityAuditService::log(
            'security_incident_updated',
            'SecurityIncident',
            $incident->id,
            "Incident {$incident->reference_number} updated to status {$request->status}"
        );

        return back()->with('success', 'Incident details updated.');
    }

    // ── DATA RETENTION & ANONYMIZATION ENGINE ──
    public function retentionIndex()
    {
        $actor = $this->getActor();
        $policies = DataRetentionPolicy::with('logs')->get();

        return view('admin.privacy.retention.index', compact('policies'));
    }

    public function executeRetention(Request $request, $id)
    {
        $actor = $this->getActor();
        $policy = DataRetentionPolicy::findOrFail($id);

        if ($policy->legal_hold) {
            return back()->with('error', "Cannot execute disposal: Category '{$policy->data_category}' is under statutory legal hold ({$policy->legal_hold_reason}).");
        }

        $cutoff = now()->subDays($policy->retention_period_days);
        $recordsEvaluated = 0;
        $recordsDisposed = 0;

        if ($policy->data_category === 'membership_records') {
            // Find inactive/expired members beyond retention cutoff
            $expiredMembers = Member::where('status', 'expired')
                ->where('expiry_date', '<', $cutoff->toDateString())
                ->where('privacy_hold', false)
                ->whereNull('privacy_anonymized_at')
                ->get();

            $recordsEvaluated = $expiredMembers->count();

            foreach ($expiredMembers as $m) {
                // Anonymize PII
                $anonTag = 'ANON-' . substr(md5((string) $m->id . config('app.key')), 0, 8);
                $m->update([
                    'full_name'             => 'Anonymized Member ' . $anonTag,
                    'email'                 => $anonTag . '@privacy.pmcc-uk.org',
                    'mobile_number'         => null,
                    'spouse_name'           => null,
                    'spouse_mobile'         => null,
                    'emergency_name'        => null,
                    'emergency_mobile'      => null,
                    'house_details'         => '[REDACTED]',
                    'post_code'             => substr((string)$m->post_code, 0, 3) . ' ***',
                    'photo'                 => null,
                    'family_photo'          => null,
                    'privacy_anonymized_at' => now(),
                    'privacy_notes'         => "Anonymized via Data Retention Policy {$policy->data_category} on " . now()->toIso8601String(),
                ]);
                $recordsDisposed++;
            }
        } elseif ($policy->data_category === 'event_bookings') {
            $oldBookings = \App\Models\EventBooking::where('created_at', '<', $cutoff)
                ->where('full_name', 'NOT LIKE', 'ANON-%')
                ->get();

            $recordsEvaluated = $oldBookings->count();
            foreach ($oldBookings as $b) {
                $b->update([
                    'full_name' => 'ANON-ATTENDEE-' . $b->id,
                    'email'     => 'anon-' . $b->id . '@privacy.pmcc-uk.org',
                    'phone'     => null,
                ]);
                $recordsDisposed++;
            }
        }

        DataRetentionLog::create([
            'policy_id'        => $policy->id,
            'execution_type'   => 'manual',
            'records_evaluated'=> $recordsEvaluated,
            'records_disposed' => $recordsDisposed,
            'disposal_action'  => $policy->disposal_action,
            'summary'          => "Retention sweep executed by {$actor->username}. {$recordsDisposed} record(s) anonymized.",
            'executed_by'      => $actor->id,
            'created_at'       => now(),
        ]);

        SecurityAuditService::log(
            'retention_sweep_executed',
            'DataRetentionPolicy',
            $policy->id,
            "Retention sweep for {$policy->data_category}: {$recordsDisposed} record(s) processed by {$actor->username}"
        );

        return back()->with('success', "Data retention sweep completed: {$recordsDisposed} expired record(s) safely processed.");
    }

    // ── AUDIT LOGS EXPLORER ──
    public function auditLogs(Request $request)
    {
        $actor = $this->getActor();

        $actionFilter = $request->input('action');
        $actorFilter = $request->input('actor');
        $correlationFilter = $request->input('correlation_id');

        $query = SecurityAuditLog::query();

        if ($actionFilter && $actionFilter !== 'all') {
            $query->where('action', $actionFilter);
        }

        if ($actorFilter) {
            $query->where('actor_username', 'LIKE', "%{$actorFilter}%");
        }

        if ($correlationFilter) {
            $query->where('correlation_id', $correlationFilter);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();
        $uniqueActions = SecurityAuditLog::select('action')->distinct()->pluck('action');

        return view('admin.privacy.audit.index', compact('logs', 'uniqueActions', 'actionFilter', 'actorFilter', 'correlationFilter'));
    }

    // ── PRIVILEGED BREAK-GLASS EMERGENCY ACCESS ──
    public function privilegedAccessIndex()
    {
        $actor = $this->getActor();
        $grants = PrivilegedAccessGrant::with(['admin', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $roles = Role::whereIn('slug', ['superadmin', 'privacy_officer', 'membership_admin'])->get();
        $admins = Admin::orderBy('username')->get();

        return view('admin.privacy.access.index', compact('grants', 'roles', 'admins'));
    }

    public function requestBreakGlass(Request $request)
    {
        $actor = $this->getActor();

        $request->validate([
            'target_role_slug' => 'required|exists:roles,slug',
            'justification'    => 'required|string|min:15|max:1000',
            'duration_hours'   => 'required|integer|min:1|max:24',
        ]);

        $grant = PrivilegedAccessGrant::create([
            'admin_id'         => $actor->id,
            'target_role_slug' => $request->target_role_slug,
            'justification'    => $request->justification,
            'granted_at'       => null,
            'expires_at'       => null,
            'status'           => 'pending',
            'ip_address'       => $request->ip(),
        ]);

        SecurityAuditService::log(
            'break_glass_requested',
            'PrivilegedAccessGrant',
            $grant->id,
            "Break-glass access requested by {$actor->username} for role {$request->target_role_slug}. Justification: {$request->justification}"
        );

        // Alert via Telegram
        try {
            \App\Services\TelegramService::sendMessage(
                "🚨 <b>Emergency Privileged Access Request</b>\n\n" .
                "👤 <b>Requester:</b> {$actor->username} (ID: {$actor->id})\n" .
                "🛡️ <b>Target Role:</b> {$request->target_role_slug}\n" .
                "📝 <b>Justification:</b> " . htmlspecialchars($request->justification) . "\n" .
                "⏱️ <b>Requested Duration:</b> {$request->duration_hours} hour(s)"
            );
        } catch (\Throwable $e) {}

        return back()->with('success', 'Break-Glass emergency access request registered. An authorized supervisor must approve it before elevated access becomes active.');
    }

    public function grantBreakGlass(Request $request, $id)
    {
        $actor = $this->getActor();
        $grant = PrivilegedAccessGrant::findOrFail($id);

        // Anti-self-approval rule
        if ($actor->id === $grant->admin_id) {
            abort(403, 'Anti-Self-Approval Enforcement: You cannot grant privileged elevation to yourself.');
        }

        $durationHours = (int) $request->input('duration_hours', 4);
        $expiresAt = now()->addHours($durationHours);

        $grant->update([
            'approved_by' => $actor->id,
            'granted_at'  => now(),
            'expires_at'  => $expiresAt,
            'status'      => 'active',
        ]);

        SecurityAuditService::log(
            'break_glass_granted',
            'PrivilegedAccessGrant',
            $grant->id,
            "Privileged access grant approved by {$actor->username} for {$grant->admin->username} until {$expiresAt->toIso8601String()}"
        );

        return back()->with('success', "Break-glass access granted to {$grant->admin->username} until " . $expiresAt->format('d M Y, h:i A') . '.');
    }

    public function revokeBreakGlass($id)
    {
        $actor = $this->getActor();
        $grant = PrivilegedAccessGrant::findOrFail($id);

        $grant->update([
            'revoked_at' => now(),
            'status'     => 'revoked',
        ]);

        SecurityAuditService::log(
            'break_glass_revoked',
            'PrivilegedAccessGrant',
            $grant->id,
            "Break-glass grant revoked by {$actor->username} for {$grant->admin->username}"
        );

        return back()->with('success', 'Privileged elevation grant revoked.');
    }

    // Action aliases for router compatibility
    public function incidentShow($id) { return $this->showIncident($id); }
    public function auditIndex(Request $request) { return $this->auditLogs($request); }
    public function requestPrivilegedAccess(Request $request) { return $this->requestBreakGlass($request); }
    public function grantPrivilegedAccess(Request $request, $id) { return $this->grantBreakGlass($request, $id); }
    public function revokePrivilegedAccess($id) { return $this->revokeBreakGlass($id); }
}
