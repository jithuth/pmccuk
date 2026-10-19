@extends('layouts.admin')

@section('page_title', 'Investigate IP')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-0">
                <i class="fas fa-search-location text-danger mr-2"></i>Suspicious IP Forensic Investigation
            </h1>
            <p class="text-muted small mb-0">Correlate and investigate an IP across all recorded authentication challenges, sessions, and security events.</p>
        </div>
    </div>

    @include('admin.security._nav')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show text-xs font-weight-bold" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <!-- IP Input Form -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.security.ip') }}" class="row g-2 align-items-center">
                <div class="col-md-9">
                    <label class="font-weight-bold text-muted text-xs mb-1">Target IPv4 or IPv6 Address</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><i class="fas fa-network-wired text-muted"></i></span>
                        </div>
                        <input type="text" name="ip" value="{{ $targetIp }}" required placeholder="e.g. 185.220.101.5 or client IP" class="form-control font-mono font-weight-bold">
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end mt-3 mt-md-0">
                    <button type="submit" class="btn btn-danger btn-block font-weight-bold">
                        <i class="fas fa-microscope mr-1"></i> Investigate IP
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($data)
        <!-- Investigation Results Header -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="h4 font-weight-bold font-mono text-dark mb-0">{{ $data['ip'] }}</h2>
                            @if($data['is_blocked'])
                                <span class="badge badge-danger px-2.5 py-1 text-xs"><i class="fas fa-ban mr-1"></i> WAF Blocked</span>
                            @else
                                <span class="badge badge-success px-2.5 py-1 text-xs"><i class="fas fa-shield-alt mr-1"></i> Firewall Active</span>
                            @endif
                        </div>
                        <div class="text-xs text-muted mt-1">
                            <i class="fas fa-map-marker-alt text-danger mr-1"></i> Approximate Location: <strong>{{ $data['location'] }}</strong>
                            &bull; First Seen: <strong>{{ $data['first_seen'] ? \Carbon\Carbon::parse($data['first_seen'])->format('d M Y H:i') : 'Never' }}</strong>
                            &bull; Last Activity: <strong>{{ $data['last_seen'] ? \Carbon\Carbon::parse($data['last_seen'])->diffForHumans() : 'Never' }}</strong>
                        </div>
                    </div>

                    <div>
                        <form action="{{ route('admin.security.ip.toggle-block') }}" method="POST" onsubmit="return confirm('Change firewall blocking state for IP {{ $data['ip'] }}?');">
                            @csrf
                            <input type="hidden" name="ip" value="{{ $data['ip'] }}">
                            @if($data['is_blocked'])
                                <button type="submit" class="btn btn-outline-success btn-sm font-weight-bold">
                                    <i class="fas fa-unlock mr-1"></i> Unblock IP in Firewall
                                </button>
                            @else
                                <button type="submit" class="btn btn-danger btn-sm font-weight-bold shadow-sm">
                                    <i class="fas fa-ban mr-1"></i> Block IP in Firewall
                                </button>
                            @endif
                        </form>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="row mt-4 pt-3 border-top text-xs">
                    <div class="col-md-3 col-6 text-center border-right">
                        <div class="text-muted uppercase font-weight-bold">Total Recorded Events</div>
                        <div class="h3 font-weight-bold text-dark mt-1">{{ number_format($data['total_events']) }}</div>
                    </div>
                    <div class="col-md-3 col-6 text-center border-right">
                        <div class="text-muted uppercase font-weight-bold">Failed / Denied Attempts</div>
                        <div class="h3 font-weight-bold text-danger mt-1">{{ number_format($data['failed_events']) }}</div>
                    </div>
                    <div class="col-md-3 col-6 text-center border-right">
                        <div class="text-muted uppercase font-weight-bold">Targeted Identifiers</div>
                        <div class="h3 font-weight-bold text-dark mt-1">{{ count($data['targeted_identifiers']) }}</div>
                    </div>
                    <div class="col-md-3 col-6 text-center">
                        <div class="text-muted uppercase font-weight-bold">Security Alerts Triggered</div>
                        <div class="h3 font-weight-bold text-{{ count($data['alerts']) > 0 ? 'warning' : 'success' }} mt-1">
                            {{ count($data['alerts']) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Targeted Accounts & Linked Alerts -->
            <div class="col-lg-5 mb-4">
                <!-- Targeted Accounts -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-users-slash mr-1 text-danger"></i> Target Account Identifiers</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush text-xs">
                            @forelse($data['targeted_identifiers'] as $tid)
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                    <span class="font-weight-bold text-dark">{{ $tid }}</span>
                                    <span class="badge badge-light border">Targeted</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted text-center py-3">No specific accounts identified.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <!-- Linked Alerts -->
                <div class="card border-0 shadow-sm" style="border-radius:12px;">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-bell mr-1 text-warning"></i> Associated Security Alerts</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush text-xs">
                            @forelse($data['alerts'] as $al)
                                <li class="list-group-item py-2.5">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge badge-{{ $al->severity === 'critical' ? 'danger' : ($al->severity === 'high' ? 'warning' : 'secondary') }} px-2">
                                            {{ strtoupper($al->severity) }}
                                        </span>
                                        <span class="text-muted font-mono">{{ $al->created_at->format('d M H:i') }}</span>
                                    </div>
                                    <div class="font-weight-bold text-dark mt-1">{{ $al->title }}</div>
                                    <p class="text-muted mb-0 mt-0.5">{{ $al->details }}</p>
                                </li>
                            @empty
                                <li class="list-group-item text-muted text-center py-3">No automated alerts for this IP.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Complete Forensic Event Timeline -->
            <div class="col-lg-7 mb-4">
                <div class="card border-0 shadow-sm" style="border-radius:12px;">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-stream mr-1 text-primary"></i> Chronological Timeline for IP {{ $data['ip'] }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height:550px;overflow-y:auto;">
                            <table class="table table-hover mb-0 text-xs">
                                <thead class="bg-light text-muted uppercase sticky-top">
                                    <tr>
                                        <th class="px-3 py-2">Timestamp</th>
                                        <th class="py-2">Actor / Target</th>
                                        <th class="py-2">Action</th>
                                        <th class="py-2">Status</th>
                                        <th class="py-2 text-right pr-3">Risk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['timeline'] as $tl)
                                        <tr>
                                            <td class="px-3 py-2 font-mono text-muted">
                                                {{ $tl->created_at->format('Y-m-d H:i:s') }}
                                            </td>
                                            <td class="py-2 font-weight-bold text-dark">
                                                {{ $tl->actor_identifier ?: 'Guest' }}
                                            </td>
                                            <td class="py-2">
                                                <code>{{ $tl->action }}</code>
                                                @if($tl->reason_code)
                                                    <div class="text-[10px] text-muted">{{ $tl->reason_code }}</div>
                                                @endif
                                            </td>
                                            <td class="py-2">
                                                <span class="badge badge-{{ $tl->status === 'success' ? 'success' : ($tl->status === 'blocked' ? 'danger' : 'warning') }} px-2 py-0.5">
                                                    {{ strtoupper($tl->status) }}
                                                </span>
                                            </td>
                                            <td class="py-2 text-right pr-3 font-weight-bold">
                                                {{ $tl->risk_score }}/100
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">No events found for this IP.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif(!empty($targetIp))
        <div class="alert alert-info text-center py-4">
            <i class="fas fa-info-circle fa-2x mb-2 d-block text-primary"></i>
            <strong>No Recorded Activity for IP: {{ $targetIp }}</strong>
            <p class="small text-muted mb-0">This IP address has not initiated any authentication requests or security events in the database.</p>
        </div>
    @endif
</div>
@endsection
