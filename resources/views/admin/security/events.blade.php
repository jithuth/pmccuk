@extends('layouts.admin')

@section('page_title', 'Auth Event Explorer')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-0">
                <i class="fas fa-list-alt text-primary mr-2"></i>Authentication Event Explorer
            </h1>
            <p class="text-muted small mb-0">Search, filter, and inspect immutable authentication and security logs.</p>
        </div>
    </div>

    @include('admin.security._nav')

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.security.events') }}" class="row g-2 align-items-center text-xs">
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">Actor / ID</label>
                    <input type="text" name="actor" value="{{ request('actor') }}" placeholder="e.g. PMCC-1001, admin" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">IP Address</label>
                    <input type="text" name="ip" value="{{ request('ip') }}" placeholder="e.g. 192.168.1.1" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">Category</label>
                    <select name="category" class="form-control form-control-sm">
                        <option value="">All Categories</option>
                        @foreach(['otp', 'mfa', 'login', 'session', 'device', 'threat', 'admin_security'] as $c)
                            <option value="{{ $c }}" {{ request('category') === $c ? 'selected' : '' }}>{{ ucfirst($c) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Statuses</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                        <option value="warning" {{ request('status') === 'warning' ? 'selected' : '' }}>Warning</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">Date Range</label>
                    <div class="d-flex gap-1">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-1 mt-4">
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold w-50">
                        <i class="fas fa-search mr-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.security.events') }}" class="btn btn-outline-secondary btn-sm w-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Events Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-xs">
                    <thead class="bg-light text-muted uppercase">
                        <tr>
                            <th class="px-3 py-2">Event UUID & Time</th>
                            <th class="py-2">Actor</th>
                            <th class="py-2">Category & Action</th>
                            <th class="py-2">Status & Reason</th>
                            <th class="py-2">IP & Device</th>
                            <th class="py-2">Risk</th>
                            <th class="py-2 text-right pr-3">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($events as $ev)
                            <tr>
                                <td class="px-3 py-2.5">
                                    <div class="font-mono text-dark font-weight-bold" style="font-size:11px;">
                                        {{ substr($ev->event_uuid, 0, 8) }}...
                                    </div>
                                    <div class="text-muted" style="font-size:10px;">{{ $ev->created_at->format('Y-m-d H:i:s') }} UTC</div>
                                </td>
                                <td class="py-2.5">
                                    <div class="font-weight-bold text-dark">{{ $ev->actor_identifier ?: 'Guest' }}</div>
                                    <span class="badge badge-light border text-[10px]">{{ $ev->actor_type }} ({{ $ev->guard ?: 'none' }})</span>
                                </td>
                                <td class="py-2.5">
                                    <span class="badge badge-primary text-[10px]">{{ strtoupper($ev->event_category) }}</span>
                                    <div class="font-weight-bold text-dark mt-0.5"><code>{{ $ev->action }}</code></div>
                                </td>
                                <td class="py-2.5">
                                    <span class="badge badge-{{ $ev->status === 'success' ? 'success' : ($ev->status === 'blocked' ? 'danger' : 'warning') }} px-2 py-0.5">
                                        {{ strtoupper($ev->status) }}
                                    </span>
                                    @if($ev->reason_code)
                                        <div class="text-muted text-[10px] mt-0.5">{{ $ev->reason_code }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5">
                                    <a href="{{ route('admin.security.ip', ['ip' => $ev->ip_address]) }}" class="font-mono font-weight-bold text-primary">
                                        {{ $ev->ip_address }}
                                    </a>
                                    <div class="text-muted text-[10px]">{{ $ev->device_name }}</div>
                                </td>
                                <td class="py-2.5">
                                    @if($ev->risk_score > 0)
                                        <span class="badge badge-{{ $ev->risk_score >= 60 ? 'danger' : ($ev->risk_score >= 30 ? 'warning' : 'secondary') }}">
                                            {{ $ev->risk_score }}/100
                                        </span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="py-2.5 text-right pr-3">
                                    <button type="button" class="btn btn-xs btn-outline-info" onclick='showEventModal(@json($ev))'>
                                        <i class="fas fa-eye"></i> JSON
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No events matching the filter criteria.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <div class="text-xs text-muted">Showing {{ $events->firstItem() ?? 0 }} to {{ $events->lastItem() ?? 0 }} of {{ $events->total() }} events</div>
                {{ $events->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal: Event JSON Metadata Inspector -->
<div class="modal fade" id="eventModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="modalTitle">Audit Event Details</h5>
                <button type="button" class="close" data-dismiss="modal" onclick="$('#eventModal').modal('hide')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <pre id="modalJson" class="bg-dark text-light p-3 rounded" style="max-height:450px;overflow-y:auto;font-size:12px;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function showEventModal(ev) {
    document.getElementById('modalTitle').innerText = 'Event: ' + ev.action + ' (' + ev.event_uuid + ')';
    document.getElementById('modalJson').innerText = JSON.stringify(ev, null, 2);
    $('#eventModal').modal('show');
}
</script>
@endsection
