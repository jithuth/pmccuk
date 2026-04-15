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
                            <button class="btn btn-outline-info btn-sm rounded-pill px-3 me-1" title="Review Changes"><i class="fas fa-eye me-1"></i> Review</button>
                            <button class="btn btn-success btn-sm rounded-pill px-3 me-1" onclick="openApproveRenewalModal({{ $r->id }}, '{{ addslashes($r->full_name) }}', {{ $r->payment_amount }})"><i class="fas fa-check me-1"></i> Approve</button>
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

<!-- APPROVE RENEWAL MODAL -->
<div class="modal fade" id="approveRenewalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow border-0">
            <form action="#" method="POST" id="approveRenewalForm">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-check-circle me-2"></i> Approve Renewal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-4">Approving renewal for: <strong id="renew_name" class="text-success"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Amount (£)</label>
                        <input type="number" step="0.01" name="amount" id="renew_amount" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Transaction Reference</label>
                        <input type="text" name="transaction_ref" class="form-control" placeholder="Update ref if needed">
                    </div>

                    <div class="alert alert-info small border-0 py-2">
                        <i class="fas fa-info-circle me-1"></i> Expiry will be extended by 1 year from current expiry or today.
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Approve & Extend</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openApproveRenewalModal(id, name, amount) {
        document.getElementById('renew_name').textContent = name;
        document.getElementById('renew_amount').value = amount;
        document.getElementById('approveRenewalForm').action = "/admin/members/renewals/" + id + "/approve";
        
        const modal = new bootstrap.Modal(document.getElementById('approveRenewalModal'));
        modal.show();
    }
</script>
@endsection
