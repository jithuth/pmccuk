@extends('layouts.admin')

@section('page_title', 'Member Management Hub')

@section('styles')
<style>
    .filter-tabs .nav-link { 
        border: none; 
        border-radius: 10px; 
        margin-right: 12px; 
        font-size: 13px; 
        font-weight: 800; 
        text-transform: uppercase; 
        letter-spacing: 0.5px;
        padding: 12px 20px; 
        background: rgba(0,0,0,0.2) !important; 
        color: rgba(255,255,255,0.7) !important;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .filter-tabs .nav-link:hover { background: rgba(0,0,0,0.3) !important; color: #fff !important; }
    .filter-tabs .nav-link.active { 
        background-color: #111827 !important; /* Very Dark Slate */
        color: #fbbf24 !important; /* Amber/Gold Text for contrast */
        box-shadow: 0 5px 15px rgba(0,0,0,0.3); 
        transform: translateY(-1px);
        border: 1px solid rgba(251,191,36,0.5) !important;
    }
    .filter-tabs .badge { 
        font-weight: 900; 
        padding: 5px 10px; 
        border-radius: 6px; 
        font-size: 11px;
        color: #fff !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .filter-tabs .nav-link.active .badge {
        box-shadow: 0 0 10px rgba(251,191,36,0.3);
    }

    .table td, .table th { vertical-align: middle !important; font-size: 13px; border-color: #e2e8f0; }
    .table thead th { 
        background: #1e293b !important; /* Dark Slate Header */
        color: #f8fafc !important; 
        font-weight: 700 !important; 
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 1px; 
        padding: 16px 12px;
    }
    
    .badge-status { 
        font-weight: 800; 
        font-size: 10px; 
        padding: 6px 12px; 
        border-radius: 8px; 
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .search-box { min-width: 320px; }
    .search-box .form-control { font-weight: 500; }
    
    .card-header { padding: 1.25rem 1.5rem; }
    
    /* Premium Pagination Styling */
    .pagination { gap: 4px; }
    .page-item .page-link { 
        border: none; border-radius: 8px !important; color: #444; font-weight: 600; font-size: 14px;
        padding: 8px 16px; transition: all 0.3s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .page-item.active .page-link { background: linear-gradient(135deg, #007bff, #0056b3); color: white; box-shadow: 0 4px 10px rgba(0,123,255,0.3); }
    .page-item:hover .page-link:not(.active) { background-color: #f8f9fa; color: #007bff; transform: translateY(-2px); }
    .page-item.disabled .page-link { opacity: 0.5; background: transparent; }
</style>
@endsection

@section('content')
@php
    $counts = [
        'active' => \App\Models\Member::where('status', 'active')->count(),
        'inactive' => \App\Models\Member::where('status', 'inactive')->count(),
        'expired' => \App\Models\Member::where('status', 'expired')->count(),
        'pending' => \App\Models\Member::where('status', 'pending')->count(),
        'deleted' => \App\Models\Member::onlyTrashed()->count(),
    ];
    $currentFilter = request('filter', 'active');
@endphp

<!-- Statistics Bar -->
<div class="row mb-3">
    @foreach($counts as $key => $count)
    @php
        $color = match($key) {
            'active' => 'success',
            'inactive' => 'warning',
            'expired' => 'danger',
            'pending' => 'info',
            'deleted' => 'secondary'
        };
        $icon = match($key) {
            'active' => 'check-circle',
            'inactive' => 'pause-circle',
            'expired' => 'clock',
            'pending' => 'hourglass-half',
            'deleted' => 'trash'
        };
    @endphp
    <div class="col-lg col-6">
        <div class="small-box bg-{{ $color }} text-white shadow-sm rounded-3 p-3 position-relative mb-3">
            <div class="inner">
                <h3 class="fw-bold mb-0">{{ $count }}</h3>
                <p class="mb-0 text-uppercase small fw-bold opacity-75">{{ $key }} Members</p>
            </div>
            <div class="position-absolute top-50 end-0 translate-middle-y me-3 opacity-25">
                <i class="fas fa-{{ $icon }} fa-3x"></i>
            </div>
            <a href="?filter={{ $key }}" class="stretched-link"></a>
        </div>
    </div>
    @endforeach
</div>

<!-- Main Table Card -->
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between flex-wrap py-3">
        <ul class="nav filter-tabs">
            @foreach(['active', 'inactive', 'expired', 'pending', 'deleted', 'all'] as $f)
                @php
                    $statusColor = match($f) {
                        'active' => '#10b981', // Emerald
                        'inactive' => '#f59e0b', // Amber
                        'expired' => '#ef4444', // Red
                        'pending' => '#06b6d4', // Cyan
                        'deleted' => '#64748b', // Slate
                        default => '#3b82f6'    // Blue
                    };
                    $isActive = ($currentFilter == $f);
                @endphp
                <li class="nav-item">
                    <a class="nav-link {{ $isActive ? 'active' : '' }}" 
                       style="background-color: {{ $isActive ? $statusColor : 'rgba(0,0,0,0.2)' }} !important; 
                              border: 2px solid {{ $statusColor }} !important;
                              color: {{ $isActive ? 'white' : 'white' }} !important;
                              {{ $isActive ? 'box-shadow: 0 0 15px '.$statusColor.'44;' : '' }}"
                       href="?filter={{ $f }}&search={{ request('search') }}">
                        {{ ucfirst($f == 'all' ? 'All' : $f) }}
                        @if(isset($counts[$f])) 
                            <span class="badge" style="background-color: {{ $statusColor }}; color: white; border: 1px solid rgba(255,255,255,0.3);">
                                {{ $counts[$f] }}
                            </span> 
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
        
        <form action="{{ route('admin.members.index') }}" method="GET" class="d-flex ms-auto search-box mt-2 mt-md-0">
            <input type="hidden" name="filter" value="{{ $currentFilter }}">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control rounded-start-pill border-0 px-3" placeholder="Search name, email, ID..." value="{{ request('search') }}">
                <button class="btn btn-light rounded-end-pill border-0" type="submit"><i class="fas fa-search px-1"></i></button>
            </div>
        </form>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-dark text-uppercase small">
                    <tr>
                        <th class="ps-3 border-0" style="width: 60px;">ID</th>
                        <th class="border-0">Member Information</th>
                        <th class="border-0">Registration No.</th>
                        <th class="border-0">Plan Type</th>
                        <th class="border-0">Status</th>
                        <th class="border-0">Expiry Date</th>
                        <th class="text-end pe-3 border-0">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $m)
                    <tr>
                        <td class="ps-3"><span class="fw-black text-primary small">#{{ $m->id }}</span></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 bg-white border border-2 me-3 shadow-sm d-flex align-items-center justify-content-center overflow-hidden" style="width: 44px; height: 44px;">
                                    <img src="{{ $m->photo_url }}" class="w-100 h-100 object-fit-cover">
                                </div>
                                <div class="lh-sm">
                                    <div class="fw-black text-slate-900" style="font-size: 14px;">{{ $m->full_name }}</div>
                                    <div class="text-muted small fw-bold opacity-75">{{ $m->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($m->membership_id_assigned)
                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2">{{ $m->membership_id_assigned }}</span>
                            @else
                                <span class="text-muted opacity-50 small">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $m->membership_type == 'Family' ? 'primary' : 'secondary' }}-subtle text-{{ $m->membership_type == 'Family' ? 'primary' : 'secondary' }} border border-{{ $m->membership_type == 'Family' ? 'primary' : 'secondary' }}-subtle">
                                {{ $m->membership_type }}
                            </span>
                        </td>
                        <td>
                            @php
                                $statusColor = match($m->status) {
                                    'active' => 'success',
                                    'pending' => 'warning text-dark',
                                    'expired' => 'danger',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge-status bg-{{ $statusColor }}">{{ strtoupper($m->status) }}</span>
                            @if($m->trashed())
                                <span class="badge-status bg-dark ms-1 text-white"><i class="fas fa-trash scale-75"></i> DELETED</span>
                            @endif
                        </td>
                        <td class="small">{{ $m->expiry_date ? \Carbon\Carbon::parse($m->expiry_date)->format('d M Y') : 'N/A' }}</td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <!-- VIEW -->
                                <button class="btn btn-info text-white" onclick="viewMember({{ $m->id }})" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <!-- EDIT -->
                                <button class="btn btn-warning text-white" onclick="editMember({{ $m->id }})" title="Edit Member">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- ID CARD & EMAIL (Only if Reg No assigned) -->
                                @if($m->membership_id_assigned)
                                    <a href="{{ route('admin.members.print-card', $m->id) }}" class="btn btn-success" title="View ID Card" target="_blank">
                                        <i class="fas fa-id-card"></i>
                                    </a>
                                    <form action="{{ route('admin.members.send-card-email', $m->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary" title="Send ID Card Email">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                @endif
                                <!-- DELETE / RESTORE -->
                                @if($m->trashed())
                                    <form action="{{ route('admin.members.restore', $m->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary text-white" title="Restore">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.members.delete', $m->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Soft delete this member?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">No members found in this category.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0 py-3">
        {{ $members->appends(request()->query())->links() }}
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Member Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div id="editLoading" class="text-center py-5 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Fetching member data...</p>
                    </div>
                    <div id="editFields" class="row">
                        <!-- Personal Details -->
                        <div class="col-md-8">
                            <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                                <h6 class="fw-bold border-bottom pb-2 mb-3 text-uppercase small text-secondary">Personal Information</h6>
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold">Title</label>
                                        <select name="title" id="e_title" class="form-select form-select-sm" required>
                                            <option value="Mr">Mr</option>
                                            <option value="Mrs">Mrs</option>
                                            <option value="Miss">Miss</option>
                                            <option value="Ms">Ms</option>
                                            <option value="Dr">Dr</option>
                                            <option value="Prof">Prof</option>
                                            <option value="Rev">Rev</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Full Name</label>
                                        <input type="text" name="full_name" id="e_full_name" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Reg. No</label>
                                        <input type="text" name="membership_id_assigned" id="e_membership_id" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Email Address</label>
                                        <input type="email" name="email" id="e_email" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Mobile Number</label>
                                        <input type="text" name="mobile_number" id="e_mobile" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Date of Birth</label>
                                        <input type="date" name="dob" id="e_dob" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Marital Status</label>
                                        <select name="marital_status" id="e_marital_status" class="form-select form-select-sm">
                                            <option value="Single">Single</option>
                                            <option value="Married">Married</option>
                                            <option value="Widowed">Widowed</option>
                                            <option value="Divorced">Divorced</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Membership Type</label>
                                        <select name="membership_type" id="e_membership_type" class="form-select form-select-sm">
                                            <option value="Single">Single</option>
                                            <option value="Family">Family</option>
                                            <option value="Student">Student</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Account Status</label>
                                        <select name="status" id="e_status" class="form-select form-select-sm">
                                            <option value="active">Active</option>
                                            <option value="pending">Pending</option>
                                            <option value="expired">Expired</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-light border-0 rounded-3 p-3">
                                <h6 class="fw-bold border-bottom pb-2 mb-3 text-uppercase small text-secondary">Address & Expirty</h6>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold">House Details / Address</label>
                                        <input type="text" name="house_details" id="e_address" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold">Post Code</label>
                                        <input type="text" name="post_code" id="e_post_code" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-danger">Membership Expiry</label>
                                        <input type="date" name="expiry_date" id="e_expiry" class="form-control form-control-sm border-danger">
                                    </div>
                                    <div class="col-md-12 border-top pt-2 mt-3">
                                        <h6 class="fw-bold text-uppercase small text-muted">Emergency Contact</h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Contact Name</label>
                                        <input type="text" name="emergency_name" id="e_emergency_name" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Contact Mobile</label>
                                        <input type="text" name="emergency_mobile" id="e_emergency_mobile" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar Info -->
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm p-3 mb-3">
                                <h6 class="fw-bold small text-uppercase">Member Photo</h6>
                                <div class="text-center mb-2">
                                    <img id="e_img_preview" src="" class="rounded border shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                                </div>
                                <input type="file" name="member_photo" class="form-control form-control-sm" onchange="previewImg(this, 'e_img_preview')">
                            </div>
                            
                            <div class="card border-0 shadow-sm p-3 mb-3">
                                <h6 class="fw-bold small text-uppercase">Family Photo</h6>
                                <div class="text-center mb-2">
                                    <img id="e_family_preview" src="" class="rounded border shadow-sm" style="width: 120px; height: 120px; object-fit: contain; background: #eee;">
                                </div>
                                <input type="file" name="family_photo" class="form-control form-control-sm" onchange="previewImg(this, 'e_family_preview')">
                            </div>

                            <div class="card border-0 shadow-sm p-3">
                                <h6 class="fw-bold small text-uppercase">Finance Info</h6>
                                <label class="small mt-2">Latest Payment Reference</label>
                                <input type="text" name="transaction_ref" id="e_txn" class="form-control form-control-sm mb-2">
                                <label class="small">Paid Amount (£)</label>
                                <input type="number" step="0.01" name="payment_amount" id="e_amount" class="form-control form-control-sm">
                            </div>
                        </div>

                        <!-- Family Details (Spouse & Children) -->
                        <div class="col-12 mt-4" id="e_family_section">
                            <div class="card border-0 shadow-sm p-3">
                                <h6 class="fw-bold border-bottom pb-2 mb-3 text-uppercase small text-primary">Family & Dependents</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="small fw-bold">Spouse Name</label>
                                        <input type="text" name="spouse_name" id="e_spouse_name" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-bold">Spouse Mobile</label>
                                        <input type="text" name="spouse_mobile" id="e_spouse_mobile" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-bold">Spouse DOB</label>
                                        <input type="date" name="spouse_dob" id="e_spouse_dob" class="form-control form-control-sm">
                                    </div>
                                </div>

                                <table class="table table-sm border">
                                    <thead class="bg-light small fw-bold">
                                        <tr>
                                            <th>Child Name</th>
                                            <th>Sex</th>
                                            <th>DOB</th>
                                            <th>Age</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="e_children_tbody">
                                        <!-- Rows injected by JS -->
                                    </tbody>
                                </table>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addChildRow()">+ Add Child Row</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold rounded-pill px-5 shadow-sm">SAVE UPDATE</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- VIEW MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white border-0 py-2">
                <h5 class="modal-title fw-bold small"><i class="fas fa-user-circle me-2"></i>Member Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="viewBody">
                <!-- Data injected by JS -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function viewMember(id) {
    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    const body = document.getElementById('viewBody');
    body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-info"></div></div>';
    modal.show();

    fetch(`/admin/members/${id}/details`)
        .then(res => res.json())
        .then(m => {
            let html = `
                <div class="row g-0">
                    <div class="col-md-8 border-end p-4">
                        <h6 class="text-primary fw-bold mb-3 d-flex align-items-center"><i class="fas fa-user me-2"></i> Personal Information</h6>
                        <table class="table table-sm table-bordered mb-4">
                            <tr><th class="bg-light w-25">Full Name</th><td class="fw-bold">${m.full_name}</td></tr>
                            <tr><th class="bg-light">Email</th><td>${m.email || 'N/A'}</td></tr>
                            <tr><th class="bg-light">Mobile</th><td>${m.mobile_number || 'N/A'}</td></tr>
                            <tr><th class="bg-light">DOB</th><td>${m.dob || 'N/A'}</td></tr>
                            <tr><th class="bg-light">Marital Status</th><td>${m.marital_status || 'N/A'}</td></tr>
                            <tr><th class="bg-light">Membership Type</th><td><span class="badge bg-info">${m.membership_type}</span></td></tr>
                        </table>

                        <h6 class="text-danger fw-bold mb-3 d-flex align-items-center"><i class="fas fa-heart me-2"></i> Spouse</h6>
                        <table class="table table-sm table-bordered mb-4">
                            <tr><th class="bg-light w-25">Spouse Name</th><td>${m.spouse_name || 'N/A'}</td></tr>
                            <tr><th class="bg-light">Spouse Mobile</th><td>${m.spouse_mobile || 'N/A'}</td></tr>
                            <tr><th class="bg-light">Spouse DOB</th><td>${m.spouse_dob || 'N/A'}</td></tr>
                        </table>

                        <h6 class="text-success fw-bold mb-3 d-flex align-items-center"><i class="fas fa-home me-2"></i> Address</h6>
                        <table class="table table-sm table-bordered mb-0">
                            <tr><th class="bg-light w-25">Address</th><td>${m.house_details || 'N/A'}</td></tr>
                            <tr><th class="bg-light">Post Code</th><td>${m.post_code || 'N/A'}</td></tr>
                        </table>
                    </div>

                    <div class="col-md-4 p-4 bg-light bg-opacity-10">
                        <h6 class="text-info fw-bold mb-3 d-flex align-items-center"><i class="fas fa-info-circle me-2"></i> Membership Status</h6>
                        <table class="table table-sm table-bordered mb-4 bg-white">
                            <tr><th class="bg-light">Status</th><td><span class="badge bg-success small">active</span></td></tr>
                            <tr><th class="bg-light">Reg. No</th><td class="fw-bold">${m.membership_id_assigned || 'N/A'}</td></tr>
                            <tr><th class="bg-light">Expiry</th><td>${m.expiry_date || 'N/A'}</td></tr>
                            <tr><th class="bg-light">Registered</th><td>${m.created_at ? new Date(m.created_at).toLocaleDateString('en-GB') : 'N/A'}</td></tr>
                        </table>

                        <div class="mb-3">
                            <a href="/admin/members/${m.id}/print-card" target="_blank" class="btn btn-success w-100 fw-bold mb-2 py-2"><i class="fas fa-id-card me-2"></i> View / Print ID Card</a>
                            <form action="/admin/members/${m.id}/send-card-email" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100 fw-bold py-2"><i class="fas fa-paper-plane me-2"></i> Send ID Card via Email</button>
                            </form>
                        </div>

                        <div class="row g-2 text-center mt-4">
                            <div class="col-6">
                                <div class="border rounded bg-white p-1 shadow-sm mb-1">
                                    <img src="${m.photo_url}" class="img-fluid rounded" style="height: 120px; width: 100%; object-fit: cover;">
                                </div>
                                <small class="text-muted fw-bold d-block"><i class="fas fa-user me-1"></i> Member Photo</small>
                            </div>
                            <div class="col-6">
                                <div class="border rounded bg-white p-1 shadow-sm mb-1 d-flex align-items-center justify-content-center" style="height: 120px;">
                                     ${m.family_photo_url ? `<img src="${m.family_photo_url}" class="img-fluid rounded" style="max-height: 100%; object-fit: contain;">` : `<div class="text-muted small">No Photo</div>`}
                                </div>
                                <small class="text-muted fw-bold d-block"><i class="fas fa-users me-1"></i> Family Photo</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            body.innerHTML = html;
        });
}

function editMember(id) {
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    const form = document.getElementById('editForm');
    const fields = document.getElementById('editFields');
    const loading = document.getElementById('editLoading');

    form.action = `/admin/members/${id}/update`;
    fields.classList.add('d-none');
    loading.classList.remove('d-none');
    modal.show();

    fetch(`/admin/members/${id}/details`)
        .then(res => res.json())
        .then(m => {
            document.getElementById('e_title').value = m.title;
            document.getElementById('e_full_name').value = m.full_name;
            document.getElementById('e_email').value = m.email;
            document.getElementById('e_mobile').value = m.mobile_number;
            document.getElementById('e_dob').value = m.dob;
            document.getElementById('e_marital_status').value = m.marital_status || 'Single';
            document.getElementById('e_membership_id').value = m.membership_id_assigned;
            document.getElementById('e_membership_type').value = m.membership_type;
            document.getElementById('e_status').value = m.status;
            document.getElementById('e_address').value = m.house_details;
            document.getElementById('e_post_code').value = m.post_code;
            document.getElementById('e_expiry').value = m.expiry_date;
            document.getElementById('e_txn').value = m.transaction_ref;
            document.getElementById('e_amount').value = m.payment_amount;
            document.getElementById('e_spouse_name').value = m.spouse_name;
            document.getElementById('e_spouse_mobile').value = m.spouse_mobile;
            document.getElementById('e_spouse_dob').value = m.spouse_dob;
            document.getElementById('e_emergency_name').value = m.emergency_name;
            document.getElementById('e_emergency_mobile').value = m.emergency_mobile;
            document.getElementById('e_img_preview').src = m.photo_url;
            document.getElementById('e_family_preview').src = m.family_photo_url || '';

            // Children rows
            const tbody = document.getElementById('e_children_tbody');
            tbody.innerHTML = '';
            if (m.children) {
                m.children.forEach(c => addChildRow(c));
            }

            loading.classList.add('d-none');
            fields.classList.remove('d-none');
        });
}

function addChildRow(c = null) {
    const tbody = document.getElementById('e_children_tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="child_name[]" class="form-control form-control-sm" value="${c ? c.child_name : ''}"></td>
        <td>
            <select name="child_sex[]" class="form-select form-select-sm">
                <option value="Male" ${c && c.sex == 'Male' ? 'selected' : ''}>Male</option>
                <option value="Female" ${c && c.sex == 'Female' ? 'selected' : ''}>Female</option>
            </select>
        </td>
        <td><input type="date" name="child_dob[]" class="form-control form-control-sm" value="${c ? c.dob : ''}"></td>
        <td><input type="text" class="form-control form-control-sm bg-light" value="${c ? c.age : ''}" readonly></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(row);
}

function previewImg(input, target) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { document.getElementById(target).src = e.target.result; }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
