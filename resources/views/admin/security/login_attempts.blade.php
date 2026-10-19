@extends('layouts.admin')

@section('page_title', 'Login Attempts')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-0">
                <i class="fas fa-sign-in-alt text-primary mr-2"></i>Login Attempts Telemetry
            </h1>
            <p class="text-muted small mb-0">Inspect authentication attempts across administrative and member entry points.</p>
        </div>
    </div>

    @include('admin.security._nav')

    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.security.logins') }}" class="row g-2 align-items-center text-xs">
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">Guard</label>
                    <select name="guard" class="form-control form-control-sm">
                        <option value="">All Guards</option>
                        <option value="admin" {{ request('guard') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="member" {{ request('guard') === 'member' ? 'selected' : '' }}>Member</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Statuses</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">User / ID</label>
                    <input type="text" name="actor" value="{{ request('actor') }}" placeholder="Username, email, ID" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">IP Address</label>
                    <input type="text" name="ip" value="{{ request('ip') }}" placeholder="Source IP" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">Date Range</label>
                    <div class="d-flex gap-1">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-1 mt-4">
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold w-50">Filter</button>
                    <a href="{{ route('admin.security.logins') }}" class="btn btn-outline-secondary btn-sm w-50">Reset</a>
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
                            <th class="px-3 py-2">Timestamp (UTC)</th>
                            <th class="py-2">User / Target</th>
                            <th class="py-2">Guard</th>
                            <th class="py-2">Action</th>
                            <th class="py-2">Status & Reason</th>
                            <th class="py-2">IP & Location</th>
                            <th class="py-2">Device</th>
                            <th class="py-2 text-right pr-3">Risk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($attempts as $att)
                            <tr>
                                <td class="px-3 py-2.5 font-mono text-muted">
                                    {{ $att->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="py-2.5 font-weight-bold text-dark">
                                    {{ $att->actor_identifier ?: 'Unauthenticated' }}
                                    @if($att->actor_id)
                                        <div class="text-[10px] text-muted">{{ $att->actor_type }} #{{ $att->actor_id }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5">
                                    <span class="badge badge-light border">{{ strtoupper($att->guard ?: 'GUEST') }}</span>
                                </td>
                                <td class="py-2.5">
                                    <code>{{ $att->action }}</code>
                                </td>
                                <td class="py-2.5">
                                    <span class="badge badge-{{ $att->status === 'success' ? 'success' : ($att->status === 'blocked' ? 'danger' : 'warning') }} px-2 py-0.5">
                                        {{ strtoupper($att->status) }}
                                    </span>
                                    @if($att->reason_code)
                                        <div class="text-[10px] text-muted mt-0.5">{{ $att->reason_code }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5">
                                    <a href="{{ route('admin.security.ip', ['ip' => $att->ip_address]) }}" class="font-mono text-primary font-weight-bold">
                                        {{ $att->ip_address }}
                                    </a>
                                    <div class="text-[10px] text-muted">{{ $att->location_summary }}</div>
                                </td>
                                <td class="py-2.5">
                                    <div>{{ $att->device_name }}</div>
                                    <div class="text-[10px] text-muted">{{ $att->browser }} on {{ $att->os }}</div>
                                </td>
                                <td class="py-2.5 text-right pr-3">
                                    @if($att->risk_score >= 50)
                                        <span class="badge badge-danger">{{ $att->risk_score }}/100</span>
                                    @elseif($att->risk_score > 0)
                                        <span class="badge badge-warning">{{ $att->risk_score }}/100</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5">No login attempts recorded matching filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <div class="text-xs text-muted">Showing {{ $attempts->firstItem() ?? 0 }} to {{ $attempts->lastItem() ?? 0 }} of {{ $attempts->total() }} records</div>
                {{ $attempts->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
