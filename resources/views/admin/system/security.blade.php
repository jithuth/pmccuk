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
                            This real-time checker audits thePlymouth Malayalee Cultural Community (PMCC-UK) software code, configuration headers, active session cookies, and database parameters against modern OWASP and secure application principles.
                        </p>
                        <a href="{{ route('admin.security-audit') }}" class="btn btn-outline-light rounded-pill px-4 py-2 text-uppercase fw-bold text-xs">
                            <i class="fas fa-sync-alt me-2"></i> Re-Scan System
                        </a>
                    </div>
                    <div class="col-md-4 text-center mt-4 mt-md-0">
                        <div class="d-inline-block position-relative">
                            <div class="rounded-circle border border-5 border-success d-flex flex-col justify-content-center align-items-center shadow-lg" style="width: 150px; height: 150px; background: rgba(255,255,255,0.03);">
                                <h1 class="fw-black mb-0 text-success text-5xl">{{ $score }}%</h1>
                                <span class="text-xs text-muted uppercase font-black tracking-wider mt-1">Audit Score</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audited Controls List -->
    <div class="col-md-12">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0 text-secondary">
                    <i class="fas fa-list-check me-2 text-primary"></i> 15 Audited System Controls
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
</div>
@endsection
