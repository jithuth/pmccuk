@extends('layouts.admin')

@section('page_title', 'Central Security Centre')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-0">
                <i class="fas fa-shield-alt text-primary mr-2"></i>Central Security Administration
            </h1>
            <p class="text-muted small mb-0">Authentication telemetry, threat detection, MFA adoption, and immutable audit logs.</p>
        </div>
        <div>
            <span class="badge badge-success px-3 py-2 text-xs font-weight-bold">
                <i class="fas fa-check-circle mr-1"></i> Central Auth Engine: Active
            </span>
        </div>
    </div>

    @include('admin.security._nav')

    <!-- Top KPI Cards -->
    <div class="row">
        <!-- Auth Events (24h) -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;border-left:4px solid #3b82f6 !important;">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Auth Events (24h)</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($total24h) }}</div>
                            <div class="text-xs text-muted mt-1">Success Rate: <strong class="text-success">{{ $successRate }}%</strong></div>
                        </div>
                        <div class="text-primary opacity-50"><i class="fas fa-bolt fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failed & Blocked -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;border-left:4px solid #ef4444 !important;">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Failed / Blocked (24h)</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($failed24h) }}</div>
                            <div class="text-xs text-muted mt-1">Suspicious requests prevented</div>
                        </div>
                        <div class="text-danger opacity-50"><i class="fas fa-user-shield fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- OTP Challenges -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;border-left:4px solid #10b981 !important;">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">OTP Challenges (24h)</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($otpTotal24h) }}</div>
                            <div class="text-xs text-muted mt-1">Verified: <strong class="text-success">{{ $otpVerified24h }}</strong></div>
                        </div>
                        <div class="text-success opacity-50"><i class="fas fa-key fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MFA Status -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;border-left:4px solid #f59e0b !important;">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">MFA Adoption</div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                {{ $mfaAdmins }}/{{ $totalAdmins }} <small class="text-xs text-muted">Admins</small>
                            </div>
                            <div class="text-xs text-muted mt-1">{{ $mfaMembers }} Members Enrolled</div>
                        </div>
                        <div class="text-warning opacity-50"><i class="fas fa-lock fa-2x"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Sessions & Threat Watch Row -->
    <div class="row">
        <!-- Live Active Sessions & Alerts Summary -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-satellite mr-1 text-primary"></i> Live Security Status</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush text-xs">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="fas fa-users text-muted mr-2"></i> Active Concurrent Sessions</span>
                            <span class="badge badge-primary badge-pill font-weight-bold">{{ $activeSessionsCount }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="fas fa-laptop text-muted mr-2"></i> Total Recognized Devices</span>
                            <span class="badge badge-info badge-pill font-weight-bold">{{ $recognizedDevicesCount }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span><i class="fas fa-bell text-danger mr-2"></i> Open Threat Alerts</span>
                            <span class="badge badge-{{ $openAlertsCount > 0 ? 'danger' : 'success' }} badge-pill font-weight-bold">
                                {{ $openAlertsCount }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Top Active IPs in 24h -->
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-network-wired mr-1 text-secondary"></i> Active Source IPs (24h)</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 text-xs">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 px-3 py-2">IP Address</th>
                                    <th class="border-0 py-2">Requests</th>
                                    <th class="border-0 py-2 text-right pr-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topIps as $ipRow)
                                    <tr>
                                        <td class="px-3 py-2 font-mono">
                                            {{ $ipRow->ip_address }}
                                            @if($ipRow->max_risk >= 50)
                                                <span class="badge badge-danger ml-1">Risk {{ $ipRow->max_risk }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2 font-weight-bold">{{ $ipRow->count }}</td>
                                        <td class="py-2 text-right pr-3">
                                            <a href="{{ route('admin.security.ip', ['ip' => $ipRow->ip_address]) }}" class="btn btn-xs btn-outline-danger font-weight-bold">
                                                Inspect
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">No activity recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Audit Events & High Risk Log -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-history mr-1 text-primary"></i> Real-Time Authentication Feed</h6>
                    <a href="{{ route('admin.security.events') }}" class="btn btn-xs btn-outline-primary font-weight-bold">
                        View All Events <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-xs">
                            <thead class="bg-light text-muted uppercase">
                                <tr>
                                    <th class="px-3 py-2">Time (UTC)</th>
                                    <th class="py-2">Actor</th>
                                    <th class="py-2">Category</th>
                                    <th class="py-2">Action</th>
                                    <th class="py-2">IP / Device</th>
                                    <th class="py-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($recentEvents as $ev)
                                    <tr>
                                        <td class="px-3 py-2.5 font-mono text-muted">
                                            {{ $ev->created_at->format('H:i:s') }}
                                        </td>
                                        <td class="py-2.5 font-weight-bold text-dark">
                                            {{ $ev->actor_identifier ?: ($ev->actor_type . ' #' . $ev->actor_id) }}
                                        </td>
                                        <td class="py-2.5">
                                            <span class="badge badge-light border">{{ strtoupper($ev->event_category) }}</span>
                                        </td>
                                        <td class="py-2.5">
                                            <code>{{ $ev->action }}</code>
                                        </td>
                                        <td class="py-2.5">
                                            <a href="{{ route('admin.security.ip', ['ip' => $ev->ip_address]) }}" class="font-mono text-primary font-weight-bold">
                                                {{ $ev->ip_address }}
                                            </a>
                                            <div class="text-muted" style="font-size:10px;">{{ $ev->device_name }}</div>
                                        </td>
                                        <td class="py-2.5 text-center">
                                            <span class="badge badge-{{ $ev->status === 'success' ? 'success' : ($ev->status === 'blocked' ? 'danger' : 'warning') }} px-2 py-1">
                                                {{ strtoupper($ev->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No audit events recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
