@extends('layouts.admin')

@section('page_title', 'New Member Registrations')

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
            <span class="info-box-icon bg-warning elevation-1 text-white"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Approvals</span>
                <span class="info-box-number h4 mb-0">{{ $members->total() }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap">
        <h3 class="card-title text-primary fw-bold mb-0"><i class="fas fa-user-plus me-2"></i>Pending Applications</h3>
        <div class="card-tools ms-auto">
            <form action="{{ route('admin.members.new') }}" method="GET" class="d-inline-block">
                <div class="input-group input-group-sm" style="width: 200px;">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                    @if(request('search'))
                        <a href="{{ route('admin.members.new') }}" class="btn btn-outline-danger"><i class="fas fa-times"></i></a>
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
                        <th class="ps-3">Applicant</th>
                        <th>Type</th>
                        <th>Mobile</th>
                        <th>Date Submitted</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $m)
                    <tr>
                        <td class="ps-3 py-3">
                            <div class="d-flex align-items-center">
                                <img src="{{ $m->photo ? asset('storage/'.$m->photo) : 'https://placehold.co/100x100?text='.substr($m->full_name,0,1) }}" class="avatar-img me-3 border shadow-sm">
                                <div>
                                    <div class="fw-bold text-dark">{{ $m->full_name }}</div>
                                    <div class="small text-muted"><i class="fas fa-envelope me-1"></i> {{ $m->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $m->membership_type == 'Family' ? 'primary' : 'secondary' }} rounded-pill px-3">
                                {{ $m->membership_type }}
                            </span>
                        </td>
                        <td><span class="text-muted"><i class="fas fa-phone-alt me-1"></i> {{ $m->mobile_number }}</span></td>
                        <td><span class="badge bg-light text-dark border">{{ \Carbon\Carbon::parse($m->created_at)->format('d M Y') }}</span></td>
                        <td class="text-end pe-3">
                            <button class="btn btn-outline-info btn-sm rounded-pill px-3 me-1" title="View Details"><i class="fas fa-eye me-1"></i> View</button>
                            <button class="btn btn-success btn-sm rounded-pill px-3 me-1" onclick="openApproveModal({{ $m->id }}, '{{ addslashes($m->full_name) }}')"><i class="fas fa-check me-1"></i> Approve</button>
                            <button class="btn btn-outline-danger btn-sm rounded-pill px-3" title="Reject"><i class="fas fa-times me-1"></i> Reject</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-25"></i>
                            <p class="mb-0">No pending registrations found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0">
        {{ $members->appends(request()->query())->links() }}
    </div>
</div>

<!-- APPROVE MODAL -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow border-0">
            <form action="#" method="POST" id="approveForm">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fas fa-check-circle me-2"></i> Approve New Member</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-4">Approving applicant: <strong id="approve_name" class="text-success"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assign Membership Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" name="membership_no" id="membership_no_input" class="form-control" placeholder="PMCC-XXX" required>
                        </div>
                        <div id="reg_no_feedback" class="form-text small text-muted mt-1">Checking availability...</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Amount (£)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 10.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Transaction Reference</label>
                        <input type="text" name="transaction_ref" class="form-control" placeholder="Bank ref, etc.">
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold">Expiry Year</label>
                        <select name="expiry_year" class="form-select">
                            <option value="{{ date('Y') + 1 }}" selected>{{ date('Y') + 1 }}</option>
                            <option value="{{ date('Y') + 2 }}">{{ date('Y') + 2 }}</option>
                            <option value="{{ date('Y') + 5 }}">{{ date('Y') + 5 }}</option>
                        </select>
                        <div class="form-text small text-info mt-1"><i class="fas fa-info-circle me-1"></i> Membership will expire 1 day before the anniversary.</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Confirm & Activate</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openApproveModal(id, name) {
        document.getElementById('approve_name').textContent = name;
        document.getElementById('approveForm').action = "/admin/members/" + id + "/approve";
        
        // Auto-generate membership number (simulated for now, would ideally fetch from API)
        const nextNo = "PMCC-" + Math.floor(100 + Math.random() * 900);
        document.getElementById('membership_no_input').value = nextNo;
        document.getElementById('reg_no_feedback').textContent = "Auto-generated suggestion";
        
        const modal = new bootstrap.Modal(document.getElementById('approveModal'));
        modal.show();
    }
</script>
@endsection
