@extends('layouts.admin')

@section('page_title', 'Security Audit')

@section('content')
<div class="row g-4">
    <!-- Header / Stats Card -->
    <div class="col-md-12">
        <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-dark text-white mb-4">
            <div class="card-body p-4 p-md-5 relative">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <span class="badge bg-primary text-uppercase font-black tracking-wider mb-2 px-3 py-2">System Guard</span>
                        <h2 class="fw-black mb-3 text-white">Security & Compliance Dashboard</h2>
                        <p class="text-slate-400 mb-4 leading-relaxed">
                            Audit Plymouth Malayalee Cultural Community (PMCC-UK) server policies, check live system controls, and manage the integrated Web Application Firewall (WAF) to prevent remote exploits.
                        </p>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.security-audit') }}" class="btn btn-outline-light rounded-pill px-4 py-2 text-uppercase fw-bold text-xs">
                                <i class="fas fa-sync-alt me-2"></i> Re-Scan System
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 text-center mt-4 mt-md-0">
                        <div class="d-inline-block position-relative">
                            <div class="rounded-circle border border-5 border-success d-flex flex-column justify-content-center align-items-center shadow-lg" style="width: 150px; height: 150px; background: rgba(255,255,255,0.03);">
                                <h1 class="fw-black mb-0 text-success text-5xl">{{ $score }}%</h1>
                                <span class="text-xs text-muted uppercase font-black tracking-wider mt-1">Audit Score</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="col-md-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-2 bg-light rounded-3">
                <ul class="nav nav-pills nav-fill" id="securityTabs" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active fw-bold py-3 text-uppercase tracking-wider text-xs" data-bs-toggle="pill" data-bs-target="#scanTab">
                            <i class="fas fa-tasks me-2"></i> Compliance Scan (15 Points)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link fw-bold py-3 text-uppercase tracking-wider text-xs" data-bs-toggle="pill" data-bs-target="#wafTab">
                            <i class="fas fa-user-shield me-2"></i> Web Application Firewall (WAF)
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Tab Contents -->
    <div class="col-md-12">
        <div class="tab-content">
            <!-- COMPLIANCE SCAN TAB -->
            <div class="tab-pane fade show active" id="scanTab">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold mb-0 text-secondary">
                            <i class="fas fa-list-check me-2 text-primary"></i> Audited System Controls
                        </h5>
                        <span class="badge bg-success-subtle text-success border border-success/15 px-3 py-2 rounded-pill font-bold">
                            {{ $passedCount }} / 15 Passed
                        </span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase text-xs tracking-wider font-bold text-slate-500">
                                <tr>
                                    <th class="ps-4 py-3" style="width: 25%;">Security Standard</th>
                                    <th style="width: 15%;">Status</th>
                                    <th style="width: 35%;">Auditor Diagnosis Details</th>
                                    <th class="pe-4" style="width: 25%;">Threat Mitigations</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                @foreach($checks as $key => $check)
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: {{ $check['status'] === 'passed' ? '#e8f5e9' : ($check['status'] === 'warning' ? '#fff3e0' : '#ffebee') }}">
                                                    <i class="fas {{ $check['status'] === 'passed' ? 'fa-shield-alt text-success' : ($check['status'] === 'warning' ? 'fa-exclamation-triangle text-warning' : 'fa-ban text-danger') }}"></i>
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-slate-800 d-block">{{ $check['name'] }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($check['status'] === 'passed')
                                                <span class="badge bg-success text-uppercase font-black text-[10px] px-2.5 py-1.5 rounded">PASSED</span>
                                            @elseif($check['status'] === 'warning')
                                                <span class="badge bg-warning text-dark text-uppercase font-black text-[10px] px-2.5 py-1.5 rounded">WARNING</span>
                                            @else
                                                <span class="badge bg-danger text-uppercase font-black text-[10px] px-2.5 py-1.5 rounded">FAILED</span>
                                            @endif
                                        </td>
                                        <td class="text-slate-600 font-medium text-xs">
                                            {{ $check['details'] }}
                                        </td>
                                        <td class="pe-4 text-slate-500 text-xs">
                                            {{ $check['desc'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- WAF PROTECTION TAB -->
            <div class="tab-pane fade" id="wafTab">
                <div class="row g-4">
                    <!-- Left: WAF Config Form -->
                    <div class="col-md-5">
                        <div class="card shadow-sm border-0 rounded-3 mb-4">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="card-title fw-bold mb-0 text-secondary">
                                    <i class="fas fa-cog me-2 text-primary"></i> WAF Firewall Settings
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <form action="{{ route('admin.security-audit.waf.update') }}" method="POST">
                                    @csrf
                                    
                                    <div class="mb-4">
                                        <label class="form-label fw-bold d-block mb-2">Firewall Protection Status</label>
                                        <div class="form-check form-switch form-check-inline p-0 d-flex align-items-center gap-3">
                                            <input type="hidden" name="waf_enabled" value="0">
                                            <input type="checkbox" name="waf_enabled" value="1" class="form-check-input ms-0" id="wafEnabledSwitch" style="width: 3.5rem; height: 1.75rem;" {{ $wafEnabled === '1' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-sm" for="wafEnabledSwitch">
                                                WAF Guard is <span class="badge {{ $wafEnabled === '1' ? 'bg-success' : 'bg-danger' }}">{{ $wafEnabled === '1' ? 'ACTIVE' : 'INACTIVE' }}</span>
                                            </label>
                                        </div>
                                        <div class="form-text mt-1 text-muted text-xs">
                                            When enabled, scans all request inputs for malicious payloads and blocks suspected exploits.
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Permanent IP Blocklist</label>
                                        <textarea name="waf_ip_blocklist" class="form-control text-mono text-sm bg-light border-0" rows="6" placeholder="Example:&#10;192.168.1.1&#10;203.0.113.50">{{ $wafIpBlocklist }}</textarea>
                                        <div class="form-text mt-1 text-muted text-xs">
                                            Enter one IP address per line. Blocked IP addresses will be completely barred from accessing any website URL.
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2.5 font-bold uppercase tracking-wider text-xs">
                                        <i class="fas fa-save me-2"></i> Save Firewall Settings
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Active Rules Indicators -->
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="card-title fw-bold mb-0 text-secondary">
                                    <i class="fas fa-shield-alt me-2 text-primary"></i> Active WAF Scan Filters
                                </h5>
                            </div>
                            <div class="card-body p-3">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-1 text-sm">
                                        <span>SQL Injection (SQLi) Protection</span>
                                        <span class="badge bg-success-subtle text-success border border-success/15 rounded-pill font-bold">Enabled</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-1 text-sm">
                                        <span>Cross-Site Scripting (XSS) Protection</span>
                                        <span class="badge bg-success-subtle text-success border border-success/15 rounded-pill font-bold">Enabled</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-1 text-sm">
                                        <span>Local/Remote File Inclusion (LFI/RFI)</span>
                                        <span class="badge bg-success-subtle text-success border border-success/15 rounded-pill font-bold">Enabled</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-1 text-sm">
                                        <span>Remote Code Execution (RCE) Blocks</span>
                                        <span class="badge bg-success-subtle text-success border border-success/15 rounded-pill font-bold">Enabled</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Right: WAF Attack Log -->
                    <div class="col-md-7">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                                <h5 class="card-title fw-bold mb-0 text-secondary">
                                    <i class="fas fa-bug me-2 text-danger"></i> Blocked Attack Records (WAF Logs)
                                </h5>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-uppercase text-xs tracking-wider font-bold text-slate-500">
                                        <tr>
                                            <th class="ps-4 py-3">Time / IP</th>
                                            <th>Attack Classification</th>
                                            <th class="pe-4">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-sm">
                                        @forelse($blockedLogs as $log)
                                            <tr>
                                                <td class="ps-4 py-3">
                                                    <span class="fw-bold text-slate-800 d-block">{{ $log->created_at->format('M d, H:i:s') }}</span>
                                                    <code class="text-muted text-xs">{{ $log->ip_address }}</code>
                                                </td>
                                                <td>
                                                    @php
                                                        $type = 'Exploit Attempt';
                                                        if (str_contains($log->details, 'SQL Injection')) $type = 'SQL Injection';
                                                        elseif (str_contains($log->details, 'Cross-Site Scripting')) $type = 'XSS Script';
                                                        elseif (str_contains($log->details, 'Path Traversal')) $type = 'Path Traversal';
                                                        elseif (str_contains($log->details, 'Remote Code Execution')) $type = 'RCE Exploit';
                                                    @endphp
                                                    <span class="badge bg-danger-subtle text-danger border border-danger/15 rounded px-2.5 py-1.5 font-bold text-[10px]">{{ strtoupper($type) }}</span>
                                                </td>
                                                <td class="pe-4 text-xs text-slate-600 font-medium">
                                                    {{ Str::limit(str_replace("WAF Blocked Attack: {$type} from IP: {$log->ip_address}. Payload: ", '', $log->details), 150) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-5 text-muted">
                                                    <i class="fas fa-shield-alt fa-3x mb-3 text-muted/30"></i>
                                                    <p class="mb-0">No malicious attack attempts have been blocked yet.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #securityTabs .nav-link { 
        color: #64748b; 
        border-radius: 0.75rem;
        transition: all 0.3s ease;
    }
    #securityTabs .nav-link.active { 
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
        
        if (tab === 'waf') {
            const el = document.querySelector('[data-bs-target="#wafTab"]');
            if (el) bootstrap.Tab.getOrCreateInstance(el).show();
        }
    });
</script>
@endsection
