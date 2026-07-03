@extends('layouts.admin')

@section('page_title', 'Membership Renewals')

@section('styles')
<style>
    .avatar-img { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; border: 2px solid #e9ecef; }
    .table td, .table th { vertical-align: middle !important; font-size: 13px; }
</style>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-4">
        <div class="info-box shadow-sm mb-3">
            <span class="info-box-icon bg-info elevation-1 text-white"><i class="fas fa-sync"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Renewals</span>
                <span class="info-box-number h4 mb-0">{{ $renewals->total() }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap">
        <h3 class="card-title text-info fw-bold mb-0"><i class="fas fa-history me-2"></i>Renewal Requests</h3>
        <div class="card-tools ms-auto">
            <form action="{{ route('admin.members.renewals') }}" method="GET" class="d-inline-block">
                <div class="input-group input-group-sm" style="width: 200px;">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                    @if(request('search'))
                        <a href="{{ route('admin.members.renewals') }}" class="btn btn-outline-danger"><i class="fas fa-times"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light text-uppercase text-secondary small">
                    <tr>
                        <th class="ps-3">Member</th>
                        <th>Old Reg. No</th>
                        <th>Payment Date</th>
                        <th>Amount</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($renewals as $r)
                    <tr>
                        <td class="ps-3 py-3">
                            <div class="d-flex align-items-center">
                                <img src="{{ $r->photo ? asset('storage/'.$r->photo) : 'https://placehold.co/100x100?text='.substr($r->full_name,0,1) }}" class="avatar-img me-3 border shadow-sm">
                                <div>
                                    <div class="fw-bold text-dark">{{ $r->full_name }}</div>
                                    <div class="small text-muted"><i class="fas fa-envelope me-1"></i> {{ $r->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-secondary rounded-pill">{{ $r->member->membership_id_assigned ?? 'N/A' }}</span></td>
                        <td>{{ $r->payment_date }}</td>
                        <td><strong class="text-success">£{{ number_format($r->payment_amount, 2) }}</strong></td>
                        <td class="text-end pe-3">
                            <button class="btn btn-outline-info btn-sm rounded-pill px-3 me-1 fw-bold" onclick="reviewRenewal({{ $r->id }})"><i class="fas fa-eye me-1"></i> Review</button>
                            <button class="btn btn-success btn-sm rounded-pill px-3 me-1 fw-bold" onclick="openApproveRenewalModal({{ $r->id }}, '{{ addslashes($r->full_name) }}', {{ $r->payment_amount }})"><i class="fas fa-check me-1"></i> Approve</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No pending renewal requests found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0">
        {{ $renewals->appends(request()->query())->links() }}
    </div>
</div>

<!-- REVIEW RENEWAL MODAL -->
<div class="modal fade" id="reviewRenewalModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-search me-2"></i> Review Renewal Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="reviewBody">
                <!-- Data injected by JS -->
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" id="btnContinueApprove">Continue to Approval <i class="fas fa-arrow-right ms-1"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- APPROVE RENEWAL MODAL -->
<div class="modal fade" id="approveRenewalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg border-0">
            <form action="#" method="POST" id="approveRenewalForm">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-check-circle me-2"></i> Approve Renewal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-file-invoice-dollar fa-3x text-success mb-3 opacity-25"></i>
                    <p class="mb-4">Confirming payment and activating membership for: <br><strong id="renew_name" class="h5 text-success"></strong></p>
                    
                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold small text-uppercase opacity-75">Payment Amount (£)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-success">£</span>
                            <input type="number" step="0.01" name="amount" id="renew_amount" class="form-control border-start-0 fw-bold" required>
                        </div>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold small text-uppercase opacity-75">Transaction Reference</label>
                        <input type="text" name="transaction_ref" id="renew_txn" class="form-control" placeholder="Optional: Add bank ref">
                    </div>

                    <div class="alert alert-warning border-0 small py-3 mt-4 text-start">
                        <i class="fas fa-info-circle me-2"></i> <strong>Note:</strong> Approving this will immediately extend the member's expiry by 1 year and send them their new ID card via email.
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm">APPROVE & ACTIVATE</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentRenewal = null;

function reviewRenewal(id) {
    const modal = new bootstrap.Modal(document.getElementById('reviewRenewalModal'));
    const body = document.getElementById('reviewBody');
    body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-info"></div><p class="mt-2 text-muted">Fetching renewal data...</p></div>';
    modal.show();

    fetch(`/admin/members/renewals/${id}/details`)
        .then(res => res.json())
        .then(r => {
            currentRenewal = r;
            let html = `
                <div class="row g-0">
                    <div class="col-md-7 border-end p-4">
                        <h6 class="text-info fw-bold mb-3 d-flex align-items-center border-bottom pb-2">
                            <i class="fas fa-user-edit me-2"></i> New Submission Details
                        </h6>
                        <table class="table table-sm table-bordered mb-4">
                            <tr><th class="bg-light w-25">Full Name</th><td class="fw-bold">${r.full_name}</td></tr>
                            <tr><th class="bg-light">Email</th><td>${r.email}</td></tr>
                            <tr><th class="bg-light">Mobile</th><td>${r.mobile_number}</td></tr>
                            <tr><th class="bg-light">Marital Status</th><td>${r.marital_status}</td></tr>
                            <tr><th class="bg-light">Membership</th><td><span class="badge bg-primary">${r.membership_type}</span></td></tr>
                            <tr><th class="bg-light">Address</th><td>${r.house_details}, ${r.post_code}</td></tr>
                        </table>

                        <h6 class="text-primary fw-bold mb-3 d-flex align-items-center border-bottom pb-2">
                            <i class="fas fa-users me-2"></i> Family Info (Requested)
                        </h6>
                        <table class="table table-sm table-bordered mb-4">
                            <tr><th class="bg-light w-25">Spouse Name</th><td>${r.spouse_name || 'N/A'}</td></tr>
                            <tr><th class="bg-light">Spouse Mobile</th><td>${r.spouse_mobile || 'N/A'}</td></tr>
                            <tr><th class="bg-light">Spouse DOB</th><td>${r.spouse_dob || 'N/A'}</td></tr>
                        </table>

                        ${r.children && r.children.length > 0 ? `
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="bg-light small fw-bold"><tr><th>Child Name</th><th>Sex</th><th>Age</th></tr></thead>
                                    <tbody>
                                        ${r.children.map(c => `<tr><td>${c.child_name}</td><td>${c.sex}</td><td>${c.age}</td></tr>`).join('')}
                                    </tbody>
                                </table>
                            </div>
                        ` : '<div class="alert alert-secondary py-2 small">No children listed.</div>'}
                    </div>

                    <div class="col-md-5 p-4 bg-light bg-opacity-10">
                        <h6 class="text-dark fw-bold mb-3 d-flex align-items-center border-bottom pb-2">
                            <i class="fas fa-file-invoice-dollar me-2"></i> Payment Verification
                        </h6>
                        <div class="card border-0 shadow-sm p-3 mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Submitted Amount:</span>
                                <span class="fw-bold text-success">£${parseFloat(r.payment_amount).toFixed(2)}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Reference:</span>
                                <span class="badge bg-secondary">${r.transaction_ref || 'N/A'}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Requested Date:</span>
                                <span>${new Date(r.created_at).toLocaleDateString()}</span>
                            </div>
                        </div>

                        <div class="row g-2 text-center">
                            <div class="col-6">
                                <div class="border rounded bg-white p-1 shadow-sm mb-1">
                                    <img src="${r.photo_url}" class="img-fluid rounded" style="height: 140px; width: 100%; object-fit: cover;">
                                </div>
                                <small class="text-muted fw-bold d-block small">NEW PHOTO</small>
                            </div>
                            <div class="col-6">
                                <div class="border rounded bg-white p-1 shadow-sm mb-1 d-flex align-items-center justify-content-center" style="height: 140px;">
                                     ${r.family_photo_url ? `<img src="${r.family_photo_url}" class="img-fluid rounded" style="max-height: 100%; object-fit: contain;">` : `<div class="text-muted small opacity-50"><i class="fas fa-users fa-2x d-block mb-1"></i> No Family Photo</div>`}
                                </div>
                                <small class="text-muted fw-bold d-block small">FAMILY PHOTO</small>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                             <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-id-badge fa-2x text-info me-3"></i>
                                <div>
                                    <div class="small text-muted">Existing Member ID:</div>
                                    <div class="fw-bold fs-5">${r.member ? r.member.membership_id_assigned : 'N/A'}</div>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>
            `;
            body.innerHTML = html;
        });

    document.getElementById('btnContinueApprove').onclick = function() {
        modal.hide();
        setTimeout(() => {
            openApproveRenewalModal(id, currentRenewal.full_name, currentRenewal.payment_amount, currentRenewal.transaction_ref);
        }, 400);
    };
}

function openApproveRenewalModal(id, name, amount, ref = '') {
    document.getElementById('renew_name').textContent = name;
    document.getElementById('renew_amount').value = amount;
    document.getElementById('renew_txn').value = ref;
    document.getElementById('approveRenewalForm').action = "/admin/members/renewals/" + id + "/approve";
    
    const modal = new bootstrap.Modal(document.getElementById('approveRenewalModal'));
    modal.show();
}
</script>
@endsection
