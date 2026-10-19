@extends('layouts.admin')

@section('page_title', 'Device History')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-0">
                <i class="fas fa-laptop text-primary mr-2"></i>Recognized Device History
            </h1>
            <p class="text-muted small mb-0">Track device fingerprints, first-seen and last-seen timestamps, and operating environments.</p>
        </div>
    </div>

    @include('admin.security._nav')

    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.security.devices') }}" class="row g-2 align-items-center text-xs">
                <div class="col-md-3">
                    <label class="font-weight-bold text-muted mb-1">User Type</label>
                    <select name="user_type" class="form-control form-control-sm">
                        <option value="">All User Types</option>
                        <option value="member" {{ request('user_type') === 'member' ? 'selected' : '' }}>Member Devices</option>
                        <option value="admin" {{ request('user_type') === 'admin' ? 'selected' : '' }}>Admin Devices</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold text-muted mb-1">Browser / OS</label>
                    <input type="text" name="browser" value="{{ request('browser') }}" placeholder="e.g. Chrome, Windows" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold text-muted mb-1">IP Address</label>
                    <input type="text" name="ip" value="{{ request('ip') }}" placeholder="Filter by IP..." class="form-control form-control-sm">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-1 mt-4">
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold w-50">Filter</button>
                    <a href="{{ route('admin.security.devices') }}" class="btn btn-outline-secondary btn-sm w-50">Reset</a>
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
                            <th class="px-3 py-2">Device Fingerprint</th>
                            <th class="py-2">Account</th>
                            <th class="py-2">Platform & Browser</th>
                            <th class="py-2">Last Known IP</th>
                            <th class="py-2">Location</th>
                            <th class="py-2">First Observed</th>
                            <th class="py-2 text-right pr-3">Last Active</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($devices as $dev)
                            <tr>
                                <td class="px-3 py-2.5 font-mono">
                                    <div class="font-weight-bold text-dark">{{ substr($dev->device_hash, 0, 12) }}...</div>
                                    <span class="badge badge-light border text-[10px]">{{ $dev->is_trusted ? 'Trusted' : 'Standard' }}</span>
                                </td>
                                <td class="py-2.5">
                                    <span class="badge badge-{{ $dev->user_type === 'admin' ? 'dark' : 'primary' }} text-[10px]">
                                        {{ strtoupper($dev->user_type) }}
                                    </span>
                                    <div class="font-weight-bold text-dark mt-0.5">
                                        @if($dev->user_type === 'admin')
                                            {{ \App\Models\Admin::find($dev->user_id)?->username ?? 'Admin #' . $dev->user_id }}
                                        @else
                                            {{ \App\Models\Member::find($dev->user_id)?->full_name ?? 'Member #' . $dev->user_id }}
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2.5">
                                    <div class="font-weight-bold text-dark">{{ $dev->device_name }}</div>
                                    <div class="text-[10px] text-muted">{{ $dev->browser }} &bull; {{ $dev->os }}</div>
                                </td>
                                <td class="py-2.5">
                                    <a href="{{ route('admin.security.ip', ['ip' => $dev->ip_address]) }}" class="font-mono text-primary font-weight-bold">
                                        {{ $dev->ip_address }}
                                    </a>
                                </td>
                                <td class="py-2.5 text-muted">
                                    {{ $dev->location ?: 'United Kingdom' }}
                                </td>
                                <td class="py-2.5 text-muted">
                                    {{ $dev->first_seen_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="py-2.5 text-right pr-3 font-weight-bold text-dark">
                                    {{ $dev->last_seen_at->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No recognized devices found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <div class="text-xs text-muted">Showing {{ $devices->firstItem() ?? 0 }} to {{ $devices->lastItem() ?? 0 }} of {{ $devices->total() }} records</div>
                {{ $devices->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
