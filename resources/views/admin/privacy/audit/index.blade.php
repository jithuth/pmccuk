@extends('layouts.admin')

@section('page_title', 'Security & Privacy Audit Log')

@section('content')
<div class="card card-outline card-info elevation-2">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-clipboard-list text-info mr-2"></i> Immutable Security Audit Trail
        </h3>
    </div>

    <!-- Filters -->
    <div class="card-body bg-light border-bottom py-3">
        <form method="GET" action="{{ route('admin.privacy.audit.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text" name="actor" value="{{ $actorFilter }}" placeholder="Filter by Actor Username..." class="form-control form-control-sm">
            </div>

            <div class="col-md-3">
                <select name="action" class="form-select form-control form-control-sm">
                    <option value="">-- All Actions --</option>
                    @foreach($uniqueActions as $act)
                        <option value="{{ $act }}" {{ $actionFilter === $act ? 'selected' : '' }}>{{ $act }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <input type="text" name="correlation_id" value="{{ $correlationFilter }}" placeholder="Search Correlation ID..." class="form-control form-control-sm font-monospace">
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-info text-white flex-grow-1 font-weight-bold">
                    <i class="fas fa-filter mr-1"></i> Filter Logs
                </button>
                <a href="{{ route('admin.privacy.audit.index') }}" class="btn btn-sm btn-outline-secondary">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm mb-0">
                <thead class="table-dark text-uppercase text-[11px]">
                    <tr>
                        <th>Timestamp</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Resource</th>
                        <th>Outcome</th>
                        <th>IP Address</th>
                        <th>Correlation ID</th>
                        <th>Justification / Details</th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap"><small>{{ $log->created_at->format('d M Y, H:i:s') }}</small></td>
                            <td>
                                <span class="badge bg-{{ $log->actor_type === 'admin' ? 'primary' : ($log->actor_type === 'member' ? 'success' : 'secondary') }}">
                                    {{ $log->actor_type }}
                                </span>
                                <strong class="ms-1">{{ $log->actor_username ?: 'Guest' }}</strong>
                            </td>
                            <td><strong class="font-monospace text-dark">{{ $log->action }}</strong></td>
                            <td>
                                @if($log->resource_type)
                                    <small class="text-muted">{{ $log->resource_type }} #{{ $log->resource_id }}</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $log->outcome === 'success' ? 'success' : ($log->outcome === 'denied' ? 'warning' : 'danger') }}">
                                    {{ strtoupper($log->outcome) }}
                                </span>
                            </td>
                            <td><small class="font-monospace text-muted">{{ $log->ip_address }}</small></td>
                            <td>
                                <small class="font-monospace text-muted" title="{{ $log->correlation_id }}">
                                    {{ substr((string)$log->correlation_id, 0, 8) }}...
                                </small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $log->justification ?: '-' }}</small>
                                @if($log->payload_summary)
                                    <span class="badge bg-light border text-dark ms-1" title="{{ json_encode($log->payload_summary) }}">
                                        Payload
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No security audit logs found matching the filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($logs->hasPages())
        <div class="card-footer bg-white border-top">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
