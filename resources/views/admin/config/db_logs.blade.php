@extends('layouts.admin')

@section('page_title', 'Database & System Logs')

@section('content')
<div class="row g-4">
    <!-- Header / Info Card -->
    <div class="col-md-12">
        <div class="card shadow-sm border-0 bg-dark text-white rounded-3">
            <div class="card-body p-4 p-md-5">
                <span class="badge bg-primary text-uppercase font-black tracking-wider mb-2 px-3 py-2">System Audit</span>
                <h2 class="fw-black mb-3 text-white">Database & System Activity Logs</h2>
                <p class="text-slate-400 mb-0 leading-relaxed">
                    Monitor administrative access events, security audits, database transactions, and inspect the raw application diagnostic log streams.
                </p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="col-md-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-2 bg-light rounded-3">
                <ul class="nav nav-pills nav-fill" id="logsTabs" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active fw-bold py-3 text-uppercase tracking-wider text-xs" data-bs-toggle="pill" data-bs-target="#activityLogsTab">
                            <i class="fas fa-history me-2"></i> Database Activity Logs
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link fw-bold py-3 text-uppercase tracking-wider text-xs" data-bs-toggle="pill" data-bs-target="#systemLogsTab">
                            <i class="fas fa-terminal me-2"></i> Application Engine Logs (laravel.log)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link fw-bold py-3 text-uppercase tracking-wider text-xs" data-bs-toggle="pill" data-bs-target="#whatsappLogsTab">
                            <i class="fab fa-whatsapp me-2 text-success"></i> WhatsApp Server &amp; Gateway Logs
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tab Contents -->
    <div class="col-md-12">
        <div class="tab-content">
            <!-- DATABASE ACTIVITY LOGS TAB -->
            <div class="tab-pane fade show active" id="activityLogsTab">
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-body p-4">
                        <!-- Filters Form -->
                        <form action="{{ route('admin.config.db-logs') }}" method="GET" class="row g-3 align-items-end mb-4">
                            <input type="hidden" name="tab" value="activity">
                            
                            <div class="col-md-4">
                                <label class="form-label text-slate-500 font-bold uppercase text-[10px] tracking-wider">Search Keywords</label>
                                <input type="text" name="search" class="form-control" placeholder="Search by username, IP, details..." value="{{ request('search') }}">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label text-slate-500 font-bold uppercase text-[10px] tracking-wider">User Type</label>
                                <select name="user_type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="admin" {{ request('user_type') == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="member" {{ request('user_type') == 'member' ? 'selected' : '' }}>Member</option>
                                    <option value="guest" {{ request('user_type') == 'guest' ? 'selected' : '' }}>Guest</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label text-slate-500 font-bold uppercase text-[10px] tracking-wider">Log Action</label>
                                <select name="action" class="form-select">
                                    <option value="">All Actions</option>
                                    @foreach($actions as $act)
                                        <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $act)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100 py-2.5 rounded text-uppercase fw-bold text-xs">
                                    Filter
                                </button>
                                <a href="{{ route('admin.config.db-logs') }}" class="btn btn-outline-secondary py-2.5 rounded text-uppercase fw-bold text-xs" title="Reset Filters">
                                    <i class="fas fa-undo"></i>
                                </a>
                            </div>
                        </form>

                        <!-- Activity Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-uppercase text-xs tracking-wider font-bold text-slate-500">
                                    <tr>
                                        <th class="ps-3 py-3" style="width: 20%;">Date / Time</th>
                                        <th style="width: 20%;">User Profile</th>
                                        <th style="width: 20%;">Action Badges</th>
                                        <th style="width: 25%;">Transaction Details</th>
                                        <th class="pe-3" style="width: 15%;">IP Address</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm">
                                    @forelse($activityLogs as $log)
                                        <tr>
                                            <td class="ps-3 font-monospace text-xs text-slate-500">
                                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                                                <div class="text-[10px] text-slate-400">{{ $log->created_at->diffForHumans() }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $log->admin_username ?: ($log->user_type ?? 'System') }}</div>
                                                <span class="badge bg-light text-dark border text-[10px]">{{ strtoupper($log->user_type ?? 'GUEST') }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $badgeClass = 'bg-secondary';
                                                    if(str_contains($log->action, 'create') || str_contains($log->action, 'add')) $badgeClass = 'bg-success';
                                                    elseif(str_contains($log->action, 'update') || str_contains($log->action, 'edit')) $badgeClass = 'bg-info text-dark';
                                                    elseif(str_contains($log->action, 'delete') || str_contains($log->action, 'remove') || str_contains($log->action, 'clear')) $badgeClass = 'bg-danger';
                                                    elseif(str_contains($log->action, 'login')) $badgeClass = 'bg-primary';
                                                @endphp
                                                <span class="badge {{ $badgeClass }} text-[10px]">{{ strtoupper(str_replace('_', ' ', $log->action)) }}</span>
                                            </td>
                                            <td class="text-slate-700 text-xs font-monospace">
                                                {{ $log->details }}
                                            </td>
                                            <td class="pe-3 font-monospace text-xs text-slate-500">
                                                {{ $log->ip_address ?: '127.0.0.1' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="fas fa-history fa-3x mb-3 text-muted/30"></i>
                                                <p class="mb-0">No matching activity log records found.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Footer -->
                        @if($activityLogs->hasPages())
                            <div class="card-footer bg-white border-0 px-0 pt-4">
                                {{ $activityLogs->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- APPLICATION ENGINE LOGS TAB -->
            <div class="tab-pane fade" id="systemLogsTab">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold mb-0 text-secondary">
                            <i class="fas fa-file-alt me-2 text-danger"></i> Raw Application Logs (laravel.log)
                        </h5>
                        <form action="{{ route('admin.config.db-logs.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete all server log entries? This action cannot be undone.');">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">
                                <i class="fas fa-trash-alt me-2"></i> Clear Log File
                            </button>
                        </form>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted text-xs mb-3">Showing tail diagnostic stream of the framework logs directory:</p>
                        <pre class="bg-dark text-light p-4 rounded-3 font-monospace text-xs overflow-auto mb-0" style="max-height: 550px; line-height: 1.5; white-space: pre-wrap;">{{ $systemLogs }}</pre>
                    </div>
                </div>
            </div>

            <!-- WHATSAPP SERVER & GATEWAY LOGS TAB -->
            <div class="tab-pane fade" id="whatsappLogsTab">
                @php
                    $waTotalLogs = count($whatsappLogs);
                    $waErrorCount = 0;
                    $waInboundCount = 0;
                    $waOutboundCount = 0;
                    foreach ($whatsappLogs as $wl) {
                        $lvl = strtoupper($wl['level'] ?? '');
                        $tp = strtolower($wl['type'] ?? '');
                        if ($lvl === 'ERROR' || str_contains(strtolower($wl['message'] ?? ''), 'error') || str_contains(strtolower($wl['message'] ?? ''), 'failed')) {
                            $waErrorCount++;
                        }
                        if ($tp === 'inbound') $waInboundCount++;
                        elseif ($tp === 'outbound') $waOutboundCount++;
                    }
                @endphp

                <!-- Telemetry Status Bar -->
                <div class="card shadow-sm border-0 rounded-3 mb-3 bg-white">
                    <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2.5 rounded-circle bg-success bg-opacity-10 text-success">
                                <i class="fab fa-whatsapp fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                    PMCC-UK WhatsApp Daemon Console
                                    @if($waStatus['connected'] ?? false)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle text-xxs rounded-pill px-2.5 py-0.5">
                                            <i class="fas fa-circle text-success me-1 pulse-dot"></i> Connected (Port 8085)
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle text-xxs rounded-pill px-2.5 py-0.5">
                                            <i class="fas fa-times-circle me-1"></i> Disconnected
                                        </span>
                                    @endif
                                </h6>
                                <span class="text-muted text-xs">
                                    Device: <strong>{{ $waStatus['user']['name'] ?? ($waStatus['user']['id'] ?? 'PMCC Executive') }}</strong> | Total Entries: <strong>{{ $waTotalLogs }}</strong> | Errors: <strong class="{{ $waErrorCount > 0 ? 'text-danger' : 'text-success' }}">{{ $waErrorCount }}</strong>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.config.db-logs', ['tab' => 'whatsapp']) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 text-xs fw-bold">
                                <i class="fas fa-sync-alt me-1"></i> Refresh Logs
                            </a>
                            <form action="{{ route('admin.config.db-logs.whatsapp-clear') }}" method="POST" onsubmit="return confirm('Clear all WhatsApp Server logs and PM2 error files?');">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 text-xs fw-bold">
                                    <i class="fas fa-trash-alt me-1"></i> Clear WhatsApp Logs
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                @if(!empty($whatsappPm2Errors))
                <!-- PM2 / Baileys STDERR Alert Panel -->
                <div class="card shadow-sm border-danger border-opacity-25 rounded-3 mb-3 bg-danger bg-opacity-10">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-2.5 px-3">
                        <span class="text-xs fw-bold text-danger text-uppercase tracking-wider">
                            <i class="fas fa-exclamation-triangle me-1"></i> Node.js Daemon STDERR (libsignal / Socket Traces)
                        </span>
                        <button class="btn btn-xs btn-outline-danger rounded-pill px-2 text-xxs" type="button" data-bs-toggle="collapse" data-bs-target="#pm2StderrCollapse">
                            Toggle Details
                        </button>
                    </div>
                    <div class="collapse show" id="pm2StderrCollapse">
                        <div class="card-body p-3 pt-0">
                            <pre class="bg-dark text-danger-emphasis p-3 rounded-2 font-monospace text-xxs overflow-auto mb-0" style="max-height: 180px; line-height: 1.4; white-space: pre-wrap; color: #f87171 !important;">{{ $whatsappPm2Errors }}</pre>
                        </div>
                    </div>
                </div>
                @endif

                <!-- WhatsApp Logs Filter & Console -->
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex flex-wrap gap-1.5 align-items-center" id="waLogFilterGroup">
                            <button type="button" class="btn btn-xs rounded-pill px-2.5 text-xxs wa-filter-btn active btn-dark" data-filter="all" onclick="filterWaLogs('all')">
                                All ({{ $waTotalLogs }})
                            </button>
                            <button type="button" class="btn btn-xs rounded-pill px-2.5 text-xxs wa-filter-btn btn-outline-danger" data-filter="error" onclick="filterWaLogs('error')">
                                <i class="fas fa-bug me-1"></i> Errors ({{ $waErrorCount }})
                            </button>
                            <button type="button" class="btn btn-xs rounded-pill px-2.5 text-xxs wa-filter-btn btn-outline-success" data-filter="inbound" onclick="filterWaLogs('inbound')">
                                <i class="fas fa-arrow-down me-1"></i> Inbound ({{ $waInboundCount }})
                            </button>
                            <button type="button" class="btn btn-xs rounded-pill px-2.5 text-xxs wa-filter-btn btn-outline-primary" data-filter="outbound" onclick="filterWaLogs('outbound')">
                                <i class="fas fa-arrow-up me-1"></i> Outbound ({{ $waOutboundCount }})
                            </button>
                        </div>
                        <input type="text" id="waLogSearchInput" class="form-control form-control-sm text-xxs rounded-pill px-3"
                               placeholder="Filter WhatsApp logs..." oninput="filterWaLogsSearch(this.value)" style="max-width: 250px;">
                    </div>
                    <div class="card-body p-0">
                        <div class="bg-dark text-light p-3 font-monospace text-xs overflow-auto" id="waLogTerminalContainer" style="max-height: 600px; line-height: 1.6; background-color: #0b0f19 !important;">
                            @forelse($whatsappLogs as $wl)
                                @php
                                    $lvl = strtoupper($wl['level'] ?? 'INFO');
                                    $tp = strtolower($wl['type'] ?? 'system');
                                    $isErr = ($lvl === 'ERROR' || str_contains(strtolower($wl['message'] ?? ''), 'error') || str_contains(strtolower($wl['message'] ?? ''), 'failed'));
                                    
                                    $lvlColor = '#38bdf8'; // info cyan
                                    if ($lvl === 'SUCCESS') $lvlColor = '#4ade80';
                                    elseif ($isErr) $lvlColor = '#f87171';
                                    elseif ($lvl === 'WARNING') $lvlColor = '#fbbf24';

                                    $ts = !empty($wl['timestamp']) ? date('Y-m-d H:i:s', strtotime($wl['timestamp'])) : '--:--:--';
                                @endphp
                                <div class="wa-log-row py-1 border-bottom border-dark-subtle" data-type="{{ $tp }}" data-level="{{ $isErr ? 'error' : strtolower($lvl) }}" data-text="{{ strtolower(($wl['message'] ?? '') . ' ' . json_encode($wl['details'] ?? '')) }}">
                                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                                        <span class="text-secondary text-xxs">[{{ $ts }}]</span>
                                        <span class="badge text-xxs px-1.5 py-0.5 rounded" style="background-color: rgba(255,255,255,0.08); color: {{ $lvlColor }}; font-weight: 700;">
                                            {{ $lvl }}
                                        </span>
                                        <span class="badge bg-secondary bg-opacity-25 text-white-50 text-xxs px-1.5 py-0.5 rounded text-uppercase">
                                            {{ $tp }}
                                        </span>
                                        <span style="color: {{ $isErr ? '#fca5a5' : '#f1f5f9' }};">
                                            {{ $wl['message'] ?? '' }}
                                        </span>
                                    </div>
                                    @if(!empty($wl['details']))
                                    <div class="ms-4 mt-0.5 text-secondary text-xxs ps-2 border-start border-secondary">
                                        <code>{{ is_string($wl['details']) ? $wl['details'] : json_encode($wl['details'], JSON_UNESCAPED_UNICODE) }}</code>
                                    </div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-center py-5 text-secondary">
                                    <i class="fab fa-whatsapp fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p>No WhatsApp server logs recorded yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #logsTabs .nav-link { 
        color: #64748b; 
        border-radius: 0.75rem;
        transition: all 0.3s ease;
    }
    #logsTabs .nav-link.active { 
        background: white !important; 
        color: var(--bs-primary) !important; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        
        if (tab === 'system') {
            const el = document.querySelector('[data-bs-target="#systemLogsTab"]');
            if (el) bootstrap.Tab.getOrCreateInstance(el).show();
        } else if (tab === 'whatsapp') {
            const el = document.querySelector('[data-bs-target="#whatsappLogsTab"]');
            if (el) bootstrap.Tab.getOrCreateInstance(el).show();
        }
    });

    let currentWaFilter = 'all';

    function filterWaLogs(filter) {
        currentWaFilter = filter;
        document.querySelectorAll('.wa-filter-btn').forEach(btn => {
            if (btn.getAttribute('data-filter') === filter) {
                btn.className = 'btn btn-xs rounded-pill px-2.5 text-xxs wa-filter-btn active btn-dark';
            } else {
                btn.className = 'btn btn-xs rounded-pill px-2.5 text-xxs wa-filter-btn btn-outline-secondary';
            }
        });
        applyWaLogFilters();
    }

    function filterWaLogsSearch(val) {
        applyWaLogFilters();
    }

    function applyWaLogFilters() {
        const searchVal = (document.getElementById('waLogSearchInput')?.value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.wa-log-row');

        rows.forEach(row => {
            const type = row.getAttribute('data-type');
            const level = row.getAttribute('data-level');
            const text = row.getAttribute('data-text') || '';

            let matchFilter = true;
            if (currentWaFilter === 'error') {
                matchFilter = (level === 'error');
            } else if (currentWaFilter === 'inbound') {
                matchFilter = (type === 'inbound');
            } else if (currentWaFilter === 'outbound') {
                matchFilter = (type === 'outbound');
            }

            const matchSearch = !searchVal || text.includes(searchVal);

            if (matchFilter && matchSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
@endsection
