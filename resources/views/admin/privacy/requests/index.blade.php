@extends('layouts.admin')

@section('page_title', 'UK GDPR Privacy Requests Management')

@section('content')
<div class="card card-outline card-primary elevation-2">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-tasks mr-2 text-primary"></i> Data Subject Rights Registry
        </h3>
    </div>

    <!-- Filters -->
    <div class="card-body bg-light border-bottom py-3">
        <form method="GET" action="{{ route('admin.privacy.requests.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search ref, name or email..." class="form-control form-control-sm">
            </div>

            <div class="col-md-3">
                <select name="type" class="form-select form-select-sm form-control form-control-sm">
                    <option value="">-- All Request Types --</option>
                    <option value="sar" {{ $filterType === 'sar' ? 'selected' : '' }}>Subject Access Request (SAR)</option>
                    <option value="rectification" {{ $filterType === 'rectification' ? 'selected' : '' }}>Rectification</option>
                    <option value="erasure" {{ $filterType === 'erasure' ? 'selected' : '' }}>Right to Erasure</option>
                    <option value="restriction" {{ $filterType === 'restriction' ? 'selected' : '' }}>Restriction of Processing</option>
                    <option value="objection" {{ $filterType === 'objection' ? 'selected' : '' }}>Objection</option>
                    <option value="portability" {{ $filterType === 'portability' ? 'selected' : '' }}>Data Portability</option>
                    <option value="consent_withdrawal" {{ $filterType === 'consent_withdrawal' ? 'selected' : '' }}>Consent Withdrawal</option>
                    <option value="complaint" {{ $filterType === 'complaint' ? 'selected' : '' }}>Privacy Complaint</option>
                </select>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm form-control form-control-sm">
                    <option value="">-- All Statuses --</option>
                    <option value="open" {{ $filterStatus === 'open' ? 'selected' : '' }}>All Open Requests</option>
                    <option value="submitted" {{ $filterStatus === 'submitted' ? 'selected' : '' }}>Submitted (New)</option>
                    <option value="identity_review" {{ $filterStatus === 'identity_review' ? 'selected' : '' }}>Identity Review</option>
                    <option value="data_discovery" {{ $filterStatus === 'data_discovery' ? 'selected' : '' }}>Data Discovery</option>
                    <option value="approval_pending" {{ $filterStatus === 'approval_pending' ? 'selected' : '' }}>Approval Pending</option>
                    <option value="fulfilment" {{ $filterStatus === 'fulfilment' ? 'selected' : '' }}>Fulfilment (Packaging)</option>
                    <option value="completed" {{ $filterStatus === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="refused" {{ $filterStatus === 'refused' ? 'selected' : '' }}>Refused</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('admin.privacy.requests.index') }}" class="btn btn-sm btn-outline-secondary">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark text-uppercase small">
                    <tr>
                        <th>Reference</th>
                        <th>Requester</th>
                        <th>Type</th>
                        <th>Received</th>
                        <th>Statutory Due Date</th>
                        <th>Identity</th>
                        <th>Status</th>
                        <th class="text-end text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td>
                                <span class="badge bg-light border text-dark font-monospace">
                                    {{ $req->reference_number }}
                                </span>
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $req->requester_name }}</div>
                                <small class="text-muted">{{ $req->requester_email }}</small>
                            </td>
                            <td>
                                <span class="badge bg-secondary text-uppercase">
                                    {{ $req->request_type }}
                                </span>
                            </td>
                            <td>
                                <small>{{ $req->received_at->format('d M Y') }}</small>
                            </td>
                            <td>
                                <div class="{{ $req->isOverdue() ? 'text-danger font-weight-bold' : '' }}">
                                    {{ $req->getEffectiveDeadline()->format('d M Y') }}
                                </div>
                                <small class="text-muted">
                                    @if(in_array($req->status, ['completed', 'refused', 'withdrawn']))
                                        Closed
                                    @elseif($req->isOverdue())
                                        <span class="text-danger font-weight-bold">{{ abs($req->getDaysRemaining()) }} days overdue</span>
                                    @else
                                        {{ $req->getDaysRemaining() }} days left
                                    @endif
                                </small>
                            </td>
                            <td>
                                @if($req->identity_verified)
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> Verified</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $req->getStatusBadgeClass() }}">
                                    {{ str_replace('_', ' ', $req->status) }}
                                </span>
                            </td>
                            <td class="text-end text-right">
                                <a href="{{ route('admin.privacy.requests.show', $req->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-folder-open"></i> Manage
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                                No privacy requests match the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($requests->hasPages())
        <div class="card-footer bg-white border-top">
            {{ $requests->links() }}
        </div>
    @endif
</div>
@endsection
