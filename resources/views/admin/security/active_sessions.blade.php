@extends('layouts.admin')

@section('page_title', 'Active Sessions')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-0">
                <i class="fas fa-satellite-dish text-primary mr-2"></i>Active Sessions Management
            </h1>
            <p class="text-muted small mb-0">Monitor live member and administrator sessions and terminate compromised connections.</p>
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
            <form method="GET" action="{{ route('admin.security.sessions') }}" class="row g-2 align-items-center text-xs">
                <div class="col-md-3">
                    <label class="font-weight-bold text-muted mb-1">User Type</label>
                    <select name="user_type" class="form-control form-control-sm">
                        <option value="">All User Types</option>
                        <option value="member" {{ request('user_type') === 'member' ? 'selected' : '' }}>Members</option>
                        <option value="admin" {{ request('user_type') === 'admin' ? 'selected' : '' }}>Administrators</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold text-muted mb-1">State</label>
                    <select name="is_active" class="form-control form-control-sm">
                        <option value="">All States</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active Sessions Only</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Revoked / Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold text-muted mb-1">Source IP</label>
                    <input type="text" name="ip" value="{{ request('ip') }}" placeholder="Filter by IP..." class="form-control form-control-sm">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-1 mt-4">
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold w-50">Filter</button>
                    <a href="{{ route('admin.security.sessions') }}" class="btn btn-outline-secondary btn-sm w-50">Reset</a>
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
                            <th class="px-3 py-2">Session Reference</th>
                            <th class="py-2">User & Guard</th>
                            <th class="py-2">Device & Platform</th>
                            <th class="py-2">IP & Location</th>
                            <th class="py-2">Last Activity</th>
                            <th class="py-2">State</th>
                            <th class="py-2 text-right pr-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sessions as $s)
                            <tr>
                                <td class="px-3 py-2.5 font-mono">
                                    <div class="font-weight-bold text-dark">#{{ $s->id }} - {{ substr($s->session_id_hash, 0, 10) }}...</div>
                                    <div class="text-[10px] text-muted">Created: {{ $s->created_at->format('Y-m-d H:i') }}</div>
                                </td>
                                <td class="py-2.5">
                                    <span class="badge badge-{{ $s->user_type === 'admin' ? 'dark' : 'primary' }} text-[10px]">
                                        {{ strtoupper($s->user_type) }}
                                    </span>
                                    <div class="font-weight-bold text-dark mt-0.5">
                                        @if($s->user_type === 'admin')
                                            {{ \App\Models\Admin::find($s->user_id)?->username ?? 'Admin #' . $s->user_id }}
                                        @else
                                            {{ \App\Models\Member::find($s->user_id)?->full_name ?? 'Member #' . $s->user_id }}
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2.5">
                                    <div class="font-weight-bold text-dark">{{ $s->device->device_name ?? 'Desktop Browser' }}</div>
                                    <div class="text-[10px] text-muted">{{ $s->device->browser ?? 'Browser' }} on {{ $s->device->os ?? 'OS' }}</div>
                                </td>
                                <td class="py-2.5">
                                    <a href="{{ route('admin.security.ip', ['ip' => $s->ip_address]) }}" class="font-mono text-primary font-weight-bold">
                                        {{ $s->ip_address }}
                                    </a>
                                    <div class="text-[10px] text-muted">{{ $s->device->location ?? 'United Kingdom' }}</div>
                                </td>
                                <td class="py-2.5">
                                    <div>{{ $s->last_activity ? \Carbon\Carbon::parse($s->last_activity)->diffForHumans() : '—' }}</div>
                                    <div class="text-[10px] text-muted">{{ $s->last_activity ? \Carbon\Carbon::parse($s->last_activity)->format('H:i:s') : '' }}</div>
                                </td>
                                <td class="py-2.5">
                                    @if($s->is_active)
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Active</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1">Revoked</span>
                                        @if($s->revoked_by)
                                            <div class="text-[10px] text-muted mt-0.5">By: {{ $s->revoked_by }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td class="py-2.5 text-right pr-3">
                                    @if($s->is_active)
                                        <form action="{{ route('admin.security.sessions.revoke') }}" method="POST" class="d-inline" onsubmit="return confirm('Immediately terminate and revoke Session #{{ $s->id }}?');">
                                            @csrf
                                            <input type="hidden" name="session_id" value="{{ $s->id }}">
                                            <button type="submit" class="btn btn-xs btn-outline-danger font-weight-bold">
                                                <i class="fas fa-ban mr-1"></i> Revoke
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted italic text-[11px]">Terminated</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No session records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <div class="text-xs text-muted">Showing {{ $sessions->firstItem() ?? 0 }} to {{ $sessions->lastItem() ?? 0 }} of {{ $sessions->total() }} records</div>
                {{ $sessions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
