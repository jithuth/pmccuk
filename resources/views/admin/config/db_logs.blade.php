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
                                            <td class="ps-3 py-3">
                                                @php
                                                    $createdAt = ($log->created_at instanceof \Carbon\Carbon) ? $log->created_at : \Illuminate\Support\Carbon::parse($log->created_at);
                                                @endphp
                                                <span class="fw-bold text-slate-800 d-block">{{ $createdAt->format('M d, Y') }}</span>
                                                <small class="text-muted text-xs">{{ $createdAt->format('H:i:s') }}</small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2 text-xs" style="width: 24px; height: 24px; background-color: {{ $log->user_type == 'admin' ? '#e0f2fe' : ($log->user_type == 'member' ? '#dcfce7' : '#f3f4f6') }}">
                                                        <i class="fas {{ $log->user_type == 'admin' ? 'fa-user-shield text-sky-600' : ($log->user_type == 'member' ? 'fa-user text-success' : 'fa-user-secret text-slate-400') }}"></i>
                                                    </div>
                                                    <div>
                                                        <span class="fw-bold text-slate-800 d-block">{{ $log->admin_username ?? 'Guest' }}</span>
                                                        <span class="text-[10px] text-uppercase text-muted fw-bold">{{ $log->user_type }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $actionClass = 'bg-secondary-subtle text-secondary';
                                                    if ($log->action == 'security_alert' || $log->action == 'waf_blocked') $actionClass = 'bg-danger-subtle text-danger border border-danger/15';
                                                    elseif (str_contains($log->action, 'login')) $actionClass = 'bg-primary-subtle text-primary border border-primary/15';
                                                    elseif (str_contains($log->action, 'delete') || str_contains($log->action, 'remove')) $actionClass = 'bg-warning-subtle text-warning border border-warning/15';
                                                    elseif (str_contains($log->action, 'create') || str_contains($log->action, 'add') || str_contains($log->action, 'approve')) $actionClass = 'bg-success-subtle text-success border border-success/15';
                                                @endphp
                                                <span class="badge {{ $actionClass }} font-bold text-[10px] uppercase px-2.5 py-1.5 rounded">
                                                    {{ str_replace('_', ' ', $log->action) }}
                                                </span>
                                            </td>
                                            <td class="text-xs text-slate-600 font-medium">
                                                {{ $log->details }}
                                            </td>
                                            <td class="pe-3">
                                                <code class="text-xs text-muted">{{ $log->ip_address }}</code>
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
        }
    });
</script>
@endsection
