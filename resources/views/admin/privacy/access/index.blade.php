@extends('layouts.admin')

@section('page_title', 'Privileged Access & Break-Glass Management')

@section('content')
<div class="row mb-3">
    <div class="col-md-8">
        <p class="text-muted small mb-0">
            Enforces the UK GDPR Principle of Least Privilege and ISO/IEC 27001 access control. Developers and technical administrators do not possess default access to personal data. In production emergencies, time-limited, audited <strong>Break-Glass Elevation</strong> may be requested with mandatory justification and independent executive supervisor approval.
        </p>
    </div>
    <div class="col-md-4 text-md-end text-md-right">
        <button type="button" class="btn btn-warning btn-sm font-weight-bold" data-bs-toggle="modal" data-bs-target="#requestBreakGlassModal">
            <i class="fas fa-bolt mr-1"></i> Request Emergency Elevation
        </button>
    </div>
</div>

<div class="card card-outline card-warning elevation-2 mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-key text-warning mr-2"></i> Privileged Access Grants
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark text-uppercase small">
                    <tr>
                        <th>Administrator</th>
                        <th>Target Role</th>
                        <th>Justification</th>
                        <th>Status</th>
                        <th>Approved By</th>
                        <th>Expires At</th>
                        <th class="text-end text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grants as $grant)
                        <tr>
                            <td>
                                <strong>{{ $grant->admin->username ?? 'Administrator' }}</strong>
                                <small class="text-muted d-block">IP: {{ $grant->ip_address }}</small>
                            </td>
                            <td><span class="badge bg-secondary text-uppercase">{{ $grant->target_role_slug }}</span></td>
                            <td><small class="text-dark">{{ $grant->justification }}</small></td>
                            <td>
                                @if($grant->isValid())
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> Active</span>
                                @elseif($grant->status === 'pending')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pending Approval</span>
                                @elseif($grant->status === 'revoked')
                                    <span class="badge bg-danger">Revoked</span>
                                @else
                                    <span class="badge bg-secondary">Expired</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $grant->approver->username ?? 'Not yet approved' }}
                                </small>
                            </td>
                            <td>
                                <small class="{{ $grant->isValid() ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                    {{ $grant->expires_at ? $grant->expires_at->format('d M Y, h:i A') : '-' }}
                                </small>
                            </td>
                            <td class="text-end text-right">
                                @if($grant->status === 'pending')
                                    @if(auth('admin')->id() !== $grant->admin_id)
                                        <form action="{{ route('admin.privacy.access.grant', $grant->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="duration_hours" value="4">
                                            <button type="submit" class="btn btn-sm btn-success font-weight-bold">
                                                <i class="fas fa-stamp mr-1"></i> Approve Elevation
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-light border text-danger" title="Anti-Self-Approval Rule: An independent supervisor must approve your request.">
                                            Self-Approval Prohibited
                                        </span>
                                    @endif
                                @elseif($grant->isValid())
                                    <form action="{{ route('admin.privacy.access.revoke', $grant->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Revoke this elevated privileged access immediately?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-ban mr-1"></i> Revoke
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted small">
                                No emergency elevation requests or active privileged grants on record.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Request Break-Glass Elevation -->
<div class="modal fade" id="requestBreakGlassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.privacy.access.request') }}" method="POST">
                @csrf
                <div class="modal-header bg-warning">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-bolt mr-2"></i> Request Break-Glass Emergency Elevation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body space-y-3">
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-info-circle mr-1"></i> All emergency access sessions are immutable and recorded in the security audit log. Supervisory notification will be dispatched synchronously via Telegram and WhatsApp.
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold small text-uppercase">Target Privileged Role</label>
                        <select name="target_role_slug" class="form-select form-control" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->slug }}">{{ $r->name }} ({{ $r->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold small text-uppercase">Requested Duration</label>
                        <select name="duration_hours" class="form-select form-control" required>
                            <option value="1">1 Hour (Quick emergency fix)</option>
                            <option value="2">2 Hours</option>
                            <option value="4" selected>4 Hours (Standard emergency maintenance)</option>
                            <option value="8">8 Hours</option>
                            <option value="24">24 Hours (Maximum allowable elevation window)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold small text-uppercase">Operational Justification / Incident Reference</label>
                        <textarea name="justification" rows="3" required placeholder="Mandatory explanation: why is elevated production access necessary? (minimum 15 characters)..." class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-paper-plane mr-1"></i> Submit Elevation Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
