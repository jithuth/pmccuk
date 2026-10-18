@extends('layouts.admin')

@section('page_title', 'Privacy & Governance Dashboard')

@section('content')
<div class="row">
    <!-- Stat 1: Open Requests -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info elevation-2">
            <div class="inner">
                <h3>{{ $kpis['total_open'] }}</h3>
                <p>Open UK GDPR Requests</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <a href="{{ route('admin.privacy.requests.index', ['status' => 'open']) }}" class="small-box-footer">
                Manage Requests <i class="fas fa-arrow-circle-right ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Stat 2: Overdue Requests -->
    <div class="col-lg-3 col-6">
        <div class="small-box {{ $kpis['overdue'] > 0 ? 'bg-danger' : 'bg-success' }} elevation-2">
            <div class="inner">
                <h3>{{ $kpis['overdue'] }}</h3>
                <p>Overdue Statutory Deadlines</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <a href="{{ route('admin.privacy.requests.index', ['status' => 'open']) }}" class="small-box-footer">
                Review Compliance <i class="fas fa-arrow-circle-right ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Stat 3: Approaching Deadlines -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning elevation-2">
            <div class="inner">
                <h3>{{ $kpis['due_this_week'] }}</h3>
                <p>Due Within 7 Days</p>
            </div>
            <div class="icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <a href="{{ route('admin.privacy.requests.index', ['status' => 'open']) }}" class="small-box-footer">
                Prioritize <i class="fas fa-arrow-circle-right ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Stat 4: Security Incidents -->
    <div class="col-lg-3 col-6">
        <div class="small-box {{ $kpis['active_incidents'] > 0 ? 'bg-danger' : 'bg-secondary' }} elevation-2">
            <div class="inner">
                <h3>{{ $kpis['active_incidents'] }}</h3>
                <p>Active Incidents (72h ICO Watch)</p>
            </div>
            <div class="icon">
                <i class="fas fa-shield-virus"></i>
            </div>
            <a href="{{ route('admin.privacy.incidents.index') }}" class="small-box-footer">
                Incident Register <i class="fas fa-arrow-circle-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Privacy Requests -->
    <div class="col-lg-8">
        <div class="card card-outline card-primary elevation-2">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-inbox text-primary mr-2"></i> Recent UK GDPR Requests
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.privacy.requests.index') }}" class="btn btn-sm btn-outline-primary">
                        View All Requests
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-valign-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th>Reference</th>
                                <th>Requester</th>
                                <th>Type</th>
                                <th>Statutory Due</th>
                                <th>Status</th>
                                <th class="text-end text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRequests as $req)
                                <tr>
                                    <td>
                                        <span class="badge bg-light border text-dark font-monospace">
                                            {{ $req->reference_number }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $req->requester_name }}</div>
                                        <small class="text-muted">{{ $req->requester_email }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-white text-uppercase">
                                            {{ $req->request_type }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="{{ $req->isOverdue() ? 'text-danger font-weight-bold' : '' }}">
                                            {{ $req->getEffectiveDeadline()->format('d M Y') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $req->getDaysRemaining() >= 0 ? $req->getDaysRemaining() . ' days left' : abs($req->getDaysRemaining()) . ' days overdue' }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $req->getStatusBadgeClass() }}">
                                            {{ str_replace('_', ' ', $req->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end text-right">
                                        <a href="{{ route('admin.privacy.requests.show', $req->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-folder-open"></i> Inspect
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No privacy requests currently registered.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Tools & Incident Watch -->
    <div class="col-lg-4">
        <!-- Quick Governance Actions -->
        <div class="card card-outline card-secondary elevation-2 mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-tools text-secondary mr-2"></i> Governance Actions
                </h3>
            </div>
            <div class="card-body p-3">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.privacy.retention.index') }}" class="btn btn-outline-dark btn-block w-100 text-start text-left mb-2">
                        <i class="fas fa-clock-rotate-left mr-2"></i> Data Retention Policies
                    </a>
                    <a href="{{ route('admin.privacy.incidents.index') }}" class="btn btn-outline-danger btn-block w-100 text-start text-left mb-2">
                        <i class="fas fa-shield-virus mr-2"></i> Report Security Breach / Incident
                    </a>
                    <a href="{{ route('admin.privacy.access.index') }}" class="btn btn-outline-warning btn-block w-100 text-start text-left mb-2">
                        <i class="fas fa-key mr-2"></i> Break-Glass Emergency Elevation
                    </a>
                    <a href="{{ route('admin.privacy.audit.index') }}" class="btn btn-outline-info btn-block w-100 text-start text-left">
                        <i class="fas fa-clipboard-list mr-2"></i> Security Audit Logs
                    </a>
                </div>
            </div>
        </div>

        <!-- 72-Hour ICO Data Breach Alerts -->
        <div class="card card-outline card-danger elevation-2">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-danger">
                    <i class="fas fa-bell text-danger mr-2"></i> Breach & Incident Watch
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($activeIncidents as $inc)
                        <li class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="font-monospace text-danger">{{ $inc->reference_number }}</strong>
                                <span class="badge bg-{{ $inc->severity === 'critical' ? 'danger' : ($inc->severity === 'high' ? 'warning' : 'secondary') }}">
                                    {{ strtoupper($inc->severity) }}
                                </span>
                            </div>
                            <div class="font-weight-bold text-dark small mb-1">{{ $inc->title }}</div>
                            <div class="text-muted text-xs">
                                Detected: {{ $inc->detected_at->format('d M Y, h:i A') }}
                            </div>
                            @if($inc->ico_notifiable)
                                <div class="mt-2 text-danger font-weight-bold small">
                                    <i class="fas fa-stopwatch"></i> ICO 72h Watch: 
                                    {{ $inc->ico_notified_at ? 'Notified on ' . $inc->ico_notified_at->format('d M') : ($inc->getIcoDeadlineHoursRemaining() . ' hrs remaining') }}
                                </div>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4 small">
                            No active data breaches or security incidents.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
