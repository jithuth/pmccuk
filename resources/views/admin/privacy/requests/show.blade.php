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
        <!-- Comprehensive Member Data Records -->
        <div class="card card-outline card-primary elevation-2 mb-4">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-primary mb-0">
                    <i class="fas fa-id-card mr-2"></i> Member Complete Data Profile (Subject of Request)
                </h3>
                @if($privacyRequest->member)
                    <span class="badge bg-primary text-white font-monospace px-2.5 py-1">
                        {{ $privacyRequest->member->membership_id_assigned ?: 'PENDING ID' }}
                    </span>
                @endif
            </div>

            @if($privacyRequest->member)
                @php $m = $privacyRequest->member; @endphp
                <div class="card-header p-2 bg-light">
                    <ul class="nav nav-pills" id="memberProfileTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active font-weight-bold text-xs text-uppercase" id="tab-admin-personal" data-toggle="pill" data-bs-toggle="pill" href="#admin-personal" role="tab">
                                <i class="fas fa-user mr-1 text-primary"></i> Personal Details
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold text-xs text-uppercase" id="tab-admin-family" data-toggle="pill" data-bs-toggle="pill" href="#admin-family" role="tab">
                                <i class="fas fa-users mr-1 text-info"></i> Family & Dependents ({{ $m->children->count() + ($m->spouse_name ? 1 : 0) }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold text-xs text-uppercase" id="tab-admin-financial" data-toggle="pill" data-bs-toggle="pill" href="#admin-financial" role="tab">
                                <i class="fas fa-receipt mr-1 text-success"></i> Financial Details ({{ $m->payments->count() + ($m->payment_amount > 0 ? 1 : 0) }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold text-xs text-uppercase" id="tab-admin-events" data-toggle="pill" data-bs-toggle="pill" href="#admin-events" role="tab">
                                <i class="fas fa-calendar-check mr-1 text-warning"></i> Event Records ({{ $eventBookings->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold text-xs text-uppercase" id="tab-admin-consents" data-toggle="pill" data-bs-toggle="pill" href="#admin-consents" role="tab">
                                <i class="fas fa-shield-alt mr-1 text-secondary"></i> GDPR Consents
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4">
                    <div class="tab-content" id="memberProfileTabContent">
                        <!-- TAB 1: Personal Details -->
                        <div class="tab-pane fade show active" id="admin-personal" role="tabpanel">
                            <div class="row align-items-center mb-4 pb-3 border-bottom">
                                <div class="col-auto">
                                    <img src="{{ $m->photo_url }}" alt="Member Photo" class="img-thumbnail rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                                </div>
                                <div class="col">
                                    <h4 class="font-weight-bold mb-1 text-dark">{{ $m->title }} {{ $m->full_name }}</h4>
                                    <div class="d-flex flex-wrap gap-2 align-items-center text-xs">
                                        <span class="badge bg-secondary font-monospace">{{ $m->membership_id_assigned ?: 'PENDING' }}</span>
                                        <span class="badge bg-light border text-dark">{{ strtoupper($m->membership_type ?: 'Standard') }}</span>
                                        <span class="badge bg-{{ strtolower($m->status) === 'active' ? 'success' : 'warning' }}">{{ strtoupper($m->getMembershipStatusLabel()) }}</span>
                                        <span class="text-muted"><i class="fas fa-calendar-alt mr-1"></i> Registered: {{ $m->created_at ? $m->created_at->format('d M Y') : 'N/A' }}</span>
                                        <span class="text-muted"><i class="fas fa-hourglass-end mr-1"></i> Expires: {{ $m->expiry_date ?: 'Permanent' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 small">
                                <div class="col-sm-6 col-md-4">
                                    <span class="text-muted d-block text-uppercase font-weight-bold text-xs">Email Address</span>
                                    <strong class="text-dark">{{ $m->email ?: 'N/A' }}</strong>
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <span class="text-muted d-block text-uppercase font-weight-bold text-xs">Mobile Phone</span>
                                    <strong class="text-dark">{{ $m->mobile_number ?: 'N/A' }}</strong>
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <span class="text-muted d-block text-uppercase font-weight-bold text-xs">Date of Birth</span>
                                    <strong class="text-dark">{{ $m->dob ?: 'N/A' }}</strong>
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <span class="text-muted d-block text-uppercase font-weight-bold text-xs">Marital Status</span>
                                    <strong class="text-dark">{{ ucfirst($m->marital_status ?: 'Unspecified') }}</strong>
                                </div>
                                <div class="col-sm-6 col-md-8">
                                    <span class="text-muted d-block text-uppercase font-weight-bold text-xs">Full Residential Address</span>
                                    <strong class="text-dark">{{ $m->house_details ?: 'House details on file' }}, {{ $m->residing_area ?: 'Plymouth' }}, {{ $m->post_code }}</strong>
                                </div>
                                <div class="col-sm-6 col-md-6">
                                    <span class="text-muted d-block text-uppercase font-weight-bold text-xs">Emergency Contact Person</span>
                                    <strong class="text-dark">{{ $m->emergency_name ?: 'None specified' }}</strong>
                                </div>
                                <div class="col-sm-6 col-md-6">
                                    <span class="text-muted d-block text-uppercase font-weight-bold text-xs">Emergency Contact Phone</span>
                                    <strong class="text-dark">{{ $m->emergency_mobile ?: 'None specified' }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Family & Dependents -->
                        <div class="tab-pane fade" id="admin-family" role="tabpanel">
                            @if($m->spouse_name)
                                <div class="p-3 bg-light rounded border mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-danger mb-1"><i class="fas fa-heart mr-1"></i> Spouse / Partner</span>
                                            <h5 class="font-weight-bold mb-0 text-dark">{{ $m->spouse_name }}</h5>
                                        </div>
                                        <div class="text-right text-xs">
                                            <div class="text-muted">Mobile: <strong>{{ $m->spouse_mobile ?: 'N/A' }}</strong></div>
                                            <div class="text-muted">DOB: <strong>{{ $m->spouse_dob ?: 'N/A' }}</strong></div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <h6 class="font-weight-bold text-secondary text-uppercase text-xs mb-2">Registered Children / Dependents ({{ $m->children->count() }})</h6>
                            @if($m->children->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm table-striped mb-0">
                                        <thead class="bg-light text-xs text-uppercase">
                                            <tr>
                                                <th>Child Name</th>
                                                <th>Gender</th>
                                                <th>Date of Birth</th>
                                                <th>Calculated Age</th>
                                            </tr>
                                        </thead>
                                        <tbody class="small">
                                            @foreach($m->children as $child)
                                                <tr>
                                                    <td class="font-weight-bold text-dark">{{ $child->child_name }}</td>
                                                    <td>{{ ucfirst($child->child_gender ?: ($child->sex ?: 'N/A')) }}</td>
                                                    <td>{{ $child->dob ?: ($child->child_dob ?: 'N/A') }}</td>
                                                    <td><span class="badge bg-light border text-dark">{{ $child->age ? $child->age . ' yrs' : 'N/A' }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted small mb-0">No dependent children registered under this member account.</p>
                            @endif
                        </div>

                        <!-- TAB 3: Financial Details -->
                        <div class="tab-pane fade" id="admin-financial" role="tabpanel">
                            <!-- Baseline Fee -->
                            <div class="p-3 bg-light rounded border mb-3">
                                <h6 class="font-weight-bold text-success text-uppercase text-xs mb-2">
                                    <i class="fas fa-wallet mr-1"></i> Membership Registration Fee Record
                                </h6>
                                <div class="row g-2 text-xs">
                                    <div class="col-sm-6 col-md-3">
                                        <span class="text-muted d-block">Fee Paid</span>
                                        <strong class="text-dark fs-6 font-monospace">£{{ number_format((float)$m->payment_amount, 2) }}</strong>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <span class="text-muted d-block">Payment Date</span>
                                        <strong class="text-dark">{{ $m->payment_date ?: ($m->created_at ? $m->created_at->format('d M Y') : 'N/A') }}</strong>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <span class="text-muted d-block">Bank Account Holder</span>
                                        <strong class="text-dark">{{ $m->bank_account_holder ?: 'N/A' }}</strong>
                                    </div>
                                    <div class="col-sm-6 col-md-3">
                                        <span class="text-muted d-block">Transaction Reference</span>
                                        <strong class="text-dark font-monospace">{{ $m->transaction_ref ?: 'N/A' }}</strong>
                                    </div>
                                </div>
                                @if($m->payment_proof)
                                    <div class="mt-2 pt-2 border-top">
                                        <a href="{{ url('img?p=' . ltrim($m->payment_proof, '/')) }}" target="_blank" class="btn btn-xs btn-outline-success font-weight-bold">
                                            <i class="fas fa-receipt mr-1"></i> View Bank Proof Document
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <!-- Additional Payments -->
                            <h6 class="font-weight-bold text-secondary text-uppercase text-xs mb-2">Payment Transaction History ({{ $m->payments->count() }})</h6>
                            @if($m->payments->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm table-hover mb-0">
                                        <thead class="bg-light text-xs text-uppercase">
                                            <tr>
                                                <th>ID</th>
                                                <th>Type / Description</th>
                                                <th>Amount (£)</th>
                                                <th>Transaction Ref</th>
                                                <th>Date Recorded</th>
                                            </tr>
                                        </thead>
                                        <tbody class="small">
                                            @foreach($m->payments as $payment)
                                                <tr>
                                                    <td>#{{ $payment->id }}</td>
                                                    <td class="font-weight-bold">{{ $payment->payment_type ?: 'Membership Fee' }}</td>
                                                    <td class="text-success font-weight-bold">£{{ number_format((float)$payment->amount, 2) }}</td>
                                                    <td class="font-monospace text-muted">{{ $payment->transaction_ref ?: 'N/A' }}</td>
                                                    <td>{{ $payment->created_at ? $payment->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted small mb-0">No supplementary payment records found in ledger.</p>
                            @endif
                        </div>

                        <!-- TAB 4: Event Records -->
                        <div class="tab-pane fade" id="admin-events" role="tabpanel">
                            <h6 class="font-weight-bold text-secondary text-uppercase text-xs mb-2">Booked Community Events & Attendance ({{ $eventBookings->count() }})</h6>
                            @if($eventBookings->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm table-hover mb-0">
                                        <thead class="bg-light text-xs text-uppercase">
                                            <tr>
                                                <th>Booking Ref</th>
                                                <th>Event Title</th>
                                                <th>Headcount Breakdown</th>
                                                <th>Amount (£)</th>
                                                <th>Booking Status</th>
                                                <th>Attendance Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="small">
                                            @foreach($eventBookings as $booking)
                                                <tr>
                                                    <td class="font-monospace font-weight-bold text-primary">{{ $booking->reference_no }}</td>
                                                    <td>
                                                        <strong class="text-dark">{{ $booking->event->title ?? 'Club Event' }}</strong>
                                                        @if(!empty($booking->event->date))
                                                            <div class="text-muted text-xs">{{ \Carbon\Carbon::parse($booking->event->date)->format('d M Y') }}</div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light border text-dark">Adults: {{ $booking->adult_count }}</span>
                                                        <span class="badge bg-light border text-dark">Children: {{ $booking->child_count }}</span>
                                                        @if($booking->student_count > 0)
                                                            <span class="badge bg-light border text-dark">Students: {{ $booking->student_count }}</span>
                                                        @endif
                                                        @if($booking->infant_count > 0)
                                                            <span class="badge bg-light border text-dark">Infants: {{ $booking->infant_count }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="font-weight-bold font-monospace">£{{ number_format((float)$booking->total_amount, 2) }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $booking->booking_status === 'confirmed' ? 'success' : 'secondary' }}">
                                                            {{ strtoupper($booking->booking_status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($booking->check_in_at)
                                                            <span class="badge bg-success"><i class="fas fa-check-circle mr-1"></i> ATTENDED</span>
                                                            <small class="d-block text-muted">{{ $booking->check_in_at->format('d M Y, h:i A') }}</small>
                                                        @else
                                                            <span class="badge bg-light border text-muted">Not Checked In</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted small mb-0">No event registrations or tickets recorded under this member's details.</p>
                            @endif
                        </div>

                        <!-- TAB 5: Consents -->
                        <div class="tab-pane fade" id="admin-consents" role="tabpanel">
                            <h6 class="font-weight-bold text-secondary text-uppercase text-xs mb-2">Statutory GDPR & Communication Consents</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead class="bg-light text-xs text-uppercase">
                                        <tr>
                                            <th>Consent Category</th>
                                            <th>Lawful Basis</th>
                                            <th>Status</th>
                                            <th>Granted At</th>
                                            <th>Revoked At</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        @forelse($m->consents as $consent)
                                            <tr>
                                                <td class="font-weight-bold">{{ $consent->consent_key }}</td>
                                                <td><span class="badge bg-light border text-dark">{{ $consent->lawful_basis }}</span></td>
                                                <td>
                                                    <span class="badge bg-{{ $consent->granted ? 'success' : 'danger' }}">
                                                        {{ $consent->granted ? 'GRANTED' : 'REVOKED' }}
                                                    </span>
                                                </td>
                                                <td>{{ $consent->granted_at ? $consent->granted_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                                <td>{{ $consent->revoked_at ? $consent->revoked_at->format('d M Y, h:i A') : '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-muted text-center py-2">No explicit electronic consent preferences logged yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="card-body p-4 text-muted small">
                    <i class="fas fa-info-circle mr-1 text-info"></i> No registered member record is linked to this request. Submitted as guest / prospective member by: <strong>{{ $privacyRequest->requester_name }}</strong> ({{ $privacyRequest->requester_email }}).
                </div>
            @endif
        </div>

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
