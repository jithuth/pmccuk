@extends('layouts.admin')

@section('page_title', 'Privacy Request ' . $privacyRequest->reference_number)

@section('content')
<!-- Header Summary -->
<div class="card elevation-2 mb-4 border-top border-4 border-primary">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-primary text-white font-monospace fs-6 px-3 py-1">
                        {{ $privacyRequest->reference_number }}
                    </span>
                    <span class="badge bg-secondary text-uppercase px-2.5 py-1">
                        {{ $privacyRequest->request_type }}
                    </span>
                    <span class="badge bg-{{ $privacyRequest->getStatusBadgeClass() }} text-uppercase px-2.5 py-1">
                        {{ str_replace('_', ' ', $privacyRequest->status) }}
                    </span>
                </div>
                <h2 class="h4 font-weight-bold text-dark mb-0">
                    {{ $privacyRequest->requester_name }}
                    <small class="text-muted fs-6 font-weight-normal">({{ $privacyRequest->requester_email }})</small>
                </h2>
            </div>

            <div class="text-md-end text-md-right">
                <div class="text-muted small font-weight-bold text-uppercase">Statutory Response Deadline</div>
                <div class="h5 font-weight-bold mb-0 {{ $privacyRequest->isOverdue() ? 'text-danger' : 'text-dark' }}">
                    {{ $privacyRequest->getEffectiveDeadline()->format('d F Y') }}
                    @if($privacyRequest->isOverdue())
                        <span class="badge bg-danger ms-1">OVERDUE</span>
                    @else
                        <span class="badge bg-light border text-dark ms-1">{{ $privacyRequest->getDaysRemaining() }} days left</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show elevation-1" role="alert">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show elevation-1" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Left Column: Request Details, Items & Documents -->
    <div class="col-lg-8">
        <!-- Scope & Details -->
        <div class="card card-outline card-secondary elevation-2 mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-file-alt text-secondary mr-2"></i> Request Specification & Details
                </h3>
            </div>
            <div class="card-body">
                <p class="text-dark mb-3">{{ $privacyRequest->details }}</p>

                @if($privacyRequest->items->count() > 0)
                    <h5 class="font-weight-bold text-secondary text-uppercase small mt-4 mb-2">Requested Item-Level Changes:</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="bg-light small">
                                <tr>
                                    <th>Category</th>
                                    <th>Field</th>
                                    <th>Current Value</th>
                                    <th>Proposed Value</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($privacyRequest->items as $item)
                                    <tr>
                                        <td>{{ $item->category }}</td>
                                        <td class="font-weight-bold">{{ $item->field_name }}</td>
                                        <td class="text-muted">{{ $item->current_value }}</td>
                                        <td class="text-success font-weight-bold">{{ $item->proposed_value }}</td>
                                        <td><span class="badge bg-light border text-dark">{{ $item->status }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Subject Access Package & Generated Documents -->
        @if($privacyRequest->request_type === 'sar')
            <div class="card card-outline card-primary elevation-2 mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-box-archive text-primary mr-2"></i> Subject Access Disclosure Package
                    </h3>
                    @if(!in_array($privacyRequest->status, ['completed', 'refused']))
                        <form action="{{ route('admin.privacy.requests.generate-sar', $privacyRequest->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-cogs mr-1"></i> Compile / Refresh SAR Disclosure
                            </button>
                        </form>
                    @endif
                </div>
                <div class="card-body">
                    @if($privacyRequest->documents->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light small text-uppercase">
                                    <tr>
                                        <th>File Name</th>
                                        <th>Format</th>
                                        <th>Size</th>
                                        <th>Token Expiry</th>
                                        <th>Downloaded</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($privacyRequest->documents as $doc)
                                        <tr>
                                            <td class="font-weight-bold font-monospace">{{ $doc->file_name }}</td>
                                            <td><span class="badge bg-info">{{ strtoupper($doc->file_type) }}</span></td>
                                            <td>{{ round($doc->file_size / 1024, 1) }} KB</td>
                                            <td>
                                                <small class="{{ $doc->isDownloadValid() ? 'text-success' : 'text-danger' }}">
                                                    {{ $doc->token_expires_at ? $doc->token_expires_at->format('d M Y, h:i A') : 'N/A' }}
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $doc->downloaded_at ? $doc->downloaded_at->format('d M Y') . ' (' . $doc->downloaded_ip . ')' : 'Not yet downloaded' }}
                                                </small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted small">
                            No disclosure package compiled yet. Click <strong>Compile / Refresh SAR Disclosure</strong> to generate structured JSON and CSV documents.
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Independent Approvals -->
        <div class="card card-outline card-warning elevation-2 mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-user-check text-warning mr-2"></i> Independent Approvals & Separation of Duties
                </h3>
            </div>
            <div class="card-body">
                @if($privacyRequest->approvals->count() > 0)
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="bg-light small text-uppercase">
                                <tr>
                                    <th>Approver</th>
                                    <th>Approval Type</th>
                                    <th>Decision</th>
                                    <th>Date</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($privacyRequest->approvals as $appr)
                                    <tr>
                                        <td class="font-weight-bold">{{ $appr->approver->username ?? 'Administrator' }}</td>
                                        <td>{{ $appr->approval_type }}</td>
                                        <td>
                                            <span class="badge bg-{{ $appr->status === 'approved' ? 'success' : 'danger' }}">
                                                {{ strtoupper($appr->status) }}
                                            </span>
                                        </td>
                                        <td><small>{{ $appr->decided_at ? $appr->decided_at->format('d M Y') : 'Pending' }}</small></td>
                                        <td><small class="text-muted">{{ $appr->comments ?: 'None' }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted small mb-3">No independent approval records have been logged yet.</p>
                @endif

                @if(!in_array($privacyRequest->status, ['completed', 'refused']))
                    <div class="p-3 bg-light rounded border">
                        <h6 class="font-weight-bold small text-uppercase mb-2">Record Independent Review Decision:</h6>
                        <form action="{{ route('admin.privacy.requests.add-approval', $privacyRequest->id) }}" method="POST" class="row g-2">
                            @csrf
                            <div class="col-md-4">
                                <select name="approval_type" class="form-select form-select-sm form-control form-control-sm" required>
                                    <option value="General Data Governance Approval">General Data Governance Approval</option>
                                    <option value="High-Risk Right to Erasure Approval">High-Risk Right to Erasure Approval</option>
                                    <option value="Bulk Personal Data Export Approval">Bulk Personal Data Export Approval</option>
                                    <option value="Statutory Refusal Approval">Statutory Refusal Approval</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="decision" class="form-select form-select-sm form-control form-control-sm" required>
                                    <option value="approved">Approve</option>
                                    <option value="rejected">Reject / Dispute</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="comments" placeholder="Reviewer notes / rationale..." class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-warning w-100 font-weight-bold">
                                    <i class="fas fa-stamp mr-1"></i> Submit
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <!-- Audit Action History -->
        <div class="card card-outline card-secondary elevation-2 mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-history text-secondary mr-2"></i> Request Action Audit Trail
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($privacyRequest->actions as $act)
                        <li class="list-group-item d-flex justify-content-between align-items-center text-xs py-2.5">
                            <div>
                                <strong class="text-dark">{{ $act->description }}</strong>
                                <small class="text-muted d-block">
                                    By: {{ $act->actor->username ?? 'System / Member' }}
                                </small>
                            </div>
                            <small class="text-muted">{{ $act->created_at->format('d M Y, h:i A') }}</small>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Governance Controls & Decision Forms -->
    <div class="col-lg-4">
        <!-- 1. Identity Verification Panel -->
        <div class="card card-outline card-info elevation-2 mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-id-badge text-info mr-2"></i> Identity Verification
                </h3>
            </div>
            <div class="card-body">
                @if($privacyRequest->identity_verified)
                    <div class="alert alert-success py-2 px-3 small mb-0">
                        <i class="fas fa-check-circle mr-1"></i> Identity verified via <strong>{{ $privacyRequest->identity_verification_method }}</strong> on {{ $privacyRequest->identity_verified_at->format('d M Y') }}.
                    </div>
                @else
                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <i class="fas fa-exclamation-circle mr-1"></i> Identity verification is pending.
                    </div>
                    <form action="{{ route('admin.privacy.requests.verify-identity', $privacyRequest->id) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small font-weight-bold">Verification Method</label>
                            <input type="text" name="method" required placeholder="e.g. Passport, Member OTP, In-Person Verification" class="form-control form-control-sm">
                        </div>
                        <button type="submit" class="btn btn-sm btn-success w-100 font-weight-bold">
                            <i class="fas fa-check mr-1"></i> Confirm Identity Verified
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- 2. Reviewer Assignment -->
        <div class="card card-outline card-secondary elevation-2 mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-user-edit text-secondary mr-2"></i> Assigned Reviewer
                </h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.privacy.requests.assign', $privacyRequest->id) }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <select name="assigned_to" class="form-select form-select-sm form-control form-control-sm" required>
                        <option value="">-- Assign Reviewer --</option>
                        @foreach($admins as $adm)
                            <option value="{{ $adm->id }}" {{ $privacyRequest->assigned_to == $adm->id ? 'selected' : '' }}>
                                {{ $adm->username }} ({{ $adm->role }})
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Assign</button>
                </form>
            </div>
        </div>

        <!-- 3. Statutory Deadline Extension -->
        <div class="card card-outline card-warning elevation-2 mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-clock text-warning mr-2"></i> Statutory Extension (Art. 12(3))
                </h3>
            </div>
            <div class="card-body small">
                @if($privacyRequest->extended_deadline)
                    <div class="alert alert-info py-2 px-3 mb-0">
                        <i class="fas fa-calendar-check mr-1"></i> Extended to <strong>{{ \Carbon\Carbon::parse($privacyRequest->extended_deadline)->format('d M Y') }}</strong>.<br>
                        <small>Reason: {{ $privacyRequest->extension_reason }}</small>
                    </div>
                @else
                    <p class="text-muted mb-2">Under UK GDPR Art. 12(3), complex or voluminous requests may be extended by up to 2 additional calendar months with recorded legal justification.</p>
                    <form action="{{ route('admin.privacy.requests.extend', $privacyRequest->id) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label font-weight-bold">Extension Duration</label>
                            <select name="extension_months" class="form-select form-select-sm form-control form-control-sm" required>
                                <option value="1">1 Additional Month</option>
                                <option value="2">2 Additional Months (Maximum Permitted)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label font-weight-bold">Legal Justification</label>
                            <textarea name="extension_reason" rows="2" required placeholder="Specify why the request is complex or voluminous..." class="form-control form-control-sm"></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-warning w-100 font-weight-bold">
                            <i class="fas fa-calendar-plus mr-1"></i> Apply Statutory Extension
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- 4. Final Decision & Closure -->
        @if(!in_array($privacyRequest->status, ['completed', 'refused']))
            <div class="card card-outline card-success elevation-2 mb-4">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-check-double mr-2"></i> Complete & Fulfil Request
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.privacy.requests.fulfil', $privacyRequest->id) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small font-weight-bold">Fulfillment Outcome</label>
                            <select name="outcome" class="form-select form-select-sm form-control form-control-sm" required>
                                <option value="completed">Completed (Fully Fulfilled)</option>
                                <option value="partially_fulfilled">Partially Fulfilled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small font-weight-bold">Outcome Reasoning / Notes</label>
                            <textarea name="decision_reasoning" rows="3" required placeholder="Summary of actions taken and secure response notification..." class="form-control form-control-sm"></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success w-100 font-weight-bold py-2">
                            <i class="fas fa-check mr-1"></i> Close Request as Fulfilled
                        </button>
                    </form>
                </div>
            </div>

            <div class="card card-outline card-danger elevation-2">
                <div class="card-header bg-danger text-white">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-ban mr-2"></i> Refuse Request
                    </h3>
                </div>
                <div class="card-body small">
                    <form action="{{ route('admin.privacy.requests.refuse', $privacyRequest->id) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label font-weight-bold">Statutory Exemption / Ground</label>
                            <input type="text" name="applicable_exemptions" placeholder="e.g. Art. 12(5) Manifestly Unfounded / Excessive, HMRC Legal Hold" class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Documented Refusal Rationale</label>
                            <textarea name="decision_reasoning" rows="3" required placeholder="Mandatory legal reasoning communicated to the data subject..." class="form-control form-control-sm"></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100 font-weight-bold">
                            <i class="fas fa-times-circle mr-1"></i> Record Formal Refusal
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="card card-outline card-secondary elevation-2">
                <div class="card-body text-center p-4">
                    <i class="fas fa-lock fa-2x text-muted mb-2 d-block"></i>
                    <h5 class="font-weight-bold text-dark">Request Formally Closed</h5>
                    <p class="text-muted small mb-0">Outcome: <strong>{{ strtoupper($privacyRequest->status) }}</strong></p>
                    <small class="text-muted">Completed: {{ $privacyRequest->completed_at ? $privacyRequest->completed_at->format('d M Y, h:i A') : 'N/A' }}</small>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
