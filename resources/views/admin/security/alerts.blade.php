@extends('layouts.admin')

@section('page_title', 'Security Alerts')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-0">
                <i class="fas fa-bell text-warning mr-2"></i>Security Alerts & Threat Incidents
            </h1>
            <p class="text-muted small mb-0">Review automated threat detections, member reports, brute-force anomalies, and credential stuffing alerts.</p>
        </div>
    </div>

    @include('admin.security._nav')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show text-xs font-weight-bold" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.security.alerts') }}" class="row g-2 align-items-center text-xs">
                <div class="col-md-3">
                    <label class="font-weight-bold text-muted mb-1">Severity</label>
                    <select name="severity" class="form-control form-control-sm">
                        <option value="">All Severities</option>
                        <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
                        <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ request('severity') === 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold text-muted mb-1">Resolution State</label>
                    <select name="is_resolved" class="form-control form-control-sm">
                        <option value="">All States</option>
                        <option value="0" {{ request('is_resolved') === '0' ? 'selected' : '' }}>Open Alerts Only</option>
                        <option value="1" {{ request('is_resolved') === '1' ? 'selected' : '' }}>Resolved Alerts Only</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold text-muted mb-1">Source IP</label>
                    <input type="text" name="ip" value="{{ request('ip') }}" placeholder="Filter by IP..." class="form-control form-control-sm">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-1 mt-4">
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold w-50">Filter</button>
                    <a href="{{ route('admin.security.alerts') }}" class="btn btn-outline-secondary btn-sm w-50">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-xs">
                    <thead class="bg-light text-muted uppercase">
                        <tr>
                            <th class="px-3 py-2">Alert ID & Time</th>
                            <th class="py-2">Severity</th>
                            <th class="py-2">Title & Incident Details</th>
                            <th class="py-2">Target / IP</th>
                            <th class="py-2">Status</th>
                            <th class="py-2 text-right pr-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($alerts as $al)
                            <tr>
                                <td class="px-3 py-2.5 font-mono">
                                    <div class="font-weight-bold text-dark">#{{ $al->id }}</div>
                                    <div class="text-[10px] text-muted">{{ $al->created_at->format('Y-m-d H:i') }} UTC</div>
                                </td>
                                <td class="py-2.5">
                                    <span class="badge badge-{{ $al->severity === 'critical' ? 'danger' : ($al->severity === 'high' ? 'warning' : ($al->severity === 'medium' ? 'info' : 'secondary')) }} px-2 py-1 uppercase">
                                        {{ $al->severity }}
                                    </span>
                                </td>
                                <td class="py-2.5" style="max-width:320px;">
                                    <div class="font-weight-bold text-dark">{{ $al->title }}</div>
                                    <p class="text-muted text-[11px] mb-0 mt-0.5 text-truncate" title="{{ $al->details }}">{{ $al->details }}</p>
                                    @if($al->is_resolved && $al->resolution_notes)
                                        <div class="text-success text-[10px] mt-1">
                                            <strong>Resolution:</strong> {{ $al->resolution_notes }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-2.5">
                                    @if($al->ip_address)
                                        <a href="{{ route('admin.security.ip', ['ip' => $al->ip_address]) }}" class="font-mono text-primary font-weight-bold">
                                            {{ $al->ip_address }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                    @if($al->user_id)
                                        <div class="text-[10px] text-muted">{{ $al->user_type }} #{{ $al->user_id }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5">
                                    @if($al->is_resolved)
                                        <span class="badge badge-success px-2 py-0.5"><i class="fas fa-check-circle mr-1"></i> Resolved</span>
                                        <div class="text-[10px] text-muted">{{ $al->resolved_at?->format('d M H:i') }}</div>
                                    @else
                                        <span class="badge badge-danger px-2 py-0.5"><i class="fas fa-exclamation-circle mr-1"></i> Open</span>
                                    @endif
                                </td>
                                <td class="py-2.5 text-right pr-3">
                                    @if(!$al->is_resolved)
                                        <button type="button" class="btn btn-xs btn-outline-success font-weight-bold" onclick="openResolveModal({{ $al->id }}, '{{ addslashes($al->title) }}')">
                                            <i class="fas fa-check"></i> Resolve
                                        </button>
                                    @else
                                        <span class="text-muted text-[11px] font-weight-bold">Closed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">No security alerts matching the filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <div class="text-xs text-muted">Showing {{ $alerts->firstItem() ?? 0 }} to {{ $alerts->lastItem() ?? 0 }} of {{ $alerts->total() }} alerts</div>
                {{ $alerts->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal: Resolve Alert -->
<div class="modal fade" id="resolveModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <form id="resolveForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="resolveModalTitle">Resolve Security Alert</h5>
                    <button type="button" class="close" data-dismiss="modal" onclick="$('#resolveModal').modal('hide')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <p class="text-xs text-muted mb-2">Provide resolution notes documenting the investigation and actions taken.</p>
                    <div class="form-group mb-0">
                        <label class="text-xs font-weight-bold text-dark">Resolution Notes</label>
                        <textarea name="resolution_notes" required rows="3" class="form-control text-xs" placeholder="e.g. Verified legitimate member travelling; IP confirmed harmless / blocked IP in firewall..."></textarea>
                    </div>
                </div>
                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="$('#resolveModal').modal('hide')">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success font-weight-bold">Mark as Resolved</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openResolveModal(id, title) {
    document.getElementById('resolveModalTitle').innerText = 'Resolve: ' + title;
    document.getElementById('resolveForm').action = "{{ url('admin/security/alerts') }}/" + id + "/resolve";
    $('#resolveModal').modal('show');
}
</script>
@endsection
