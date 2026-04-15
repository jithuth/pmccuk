@extends('layouts.admin')

@section('page_title', 'System Activity Logs')

@section('content')
<div class="card shadow-sm border-0 rounded-3">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="card-title fw-bold mb-0 text-secondary">Admin Audit Trail</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th class="ps-3 border-0">Timestamp</th>
                        <th class="border-0">Admin</th>
                        <th class="border-0">Action</th>
                        <th class="border-0">Details</th>
                        <th class="border-0">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="ps-3 small text-muted">
                            {{ \Carbon\Carbon::parse($log->created_at)->format('d M, H:i:s') }}
                        </td>
                        <td>
                            <div class="fw-bold">{{ $log->admin_username }}</div>
                            <div class="text-[10px] text-muted text-uppercase">{{ $log->user_type }}</div>
                        </td>
                        <td>
                            @php
                                $color = 'secondary';
                                if(str_contains($log->action, 'approve')) $color = 'success';
                                if(str_contains($log->action, 'delete')) $color = 'danger';
                                if(str_contains($log->action, 'login')) $color = 'info';
                            @endphp
                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }}-subtle px-2 rounded-pill small fw-bold">
                                {{ strtoupper(str_replace('_', ' ', $log->action)) }}
                            </span>
                        </td>
                        <td>
                            <div class="small text-dark" style="max-width: 400px; white-space: normal;">{{ $log->details }}</div>
                        </td>
                        <td class="small font-monospace">
                            {{ $log->ip_address }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No activity logs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0">
        {{ $logs->links() }}
    </div>
</div>
@endsection
