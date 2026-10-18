@extends('layouts.admin')

@section('page_title', 'Security Incident & Data Breach Register')

@section('content')
<div class="row mb-3">
    <div class="col-md-8">
        <p class="text-muted small mb-0">
            Formal register for reporting, containing, and evaluating personal data breaches under UK GDPR Articles 33 & 34. Where a breach poses a risk to rights and freedoms of individuals, notification to the Information Commissioner's Office (ICO) must occur within <strong>72 hours</strong>.
        </p>
    </div>
    <div class="col-md-4 text-md-end text-md-right">
        <button type="button" class="btn btn-danger btn-sm font-weight-bold" data-bs-toggle="modal" data-bs-target="#newIncidentModal">
            <i class="fas fa-plus-circle mr-1"></i> Log Security Incident
        </button>
    </div>
</div>

<div class="card card-outline card-danger elevation-2">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-shield-virus text-danger mr-2"></i> Active Incidents & Historical Log
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark text-uppercase small">
                    <tr>
                        <th>Reference</th>
                        <th>Title</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Detected At</th>
                        <th>ICO 72h Status</th>
                        <th>Reporter</th>
                        <th class="text-end text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incidents as $inc)
                        <tr>
                            <td><strong class="font-monospace text-danger">{{ $inc->reference_number }}</strong></td>
                            <td class="font-weight-bold">{{ $inc->title }}</td>
                            <td>
                                <span class="badge bg-{{ $inc->severity === 'critical' ? 'danger' : ($inc->severity === 'high' ? 'warning' : 'secondary') }}">
                                    {{ strtoupper($inc->severity) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $inc->status === 'closed' ? 'success' : 'primary' }}">
                                    {{ strtoupper($inc->status) }}
                                </span>
                            </td>
                            <td><small>{{ $inc->detected_at->format('d M Y, h:i A') }}</small></td>
                            <td>
                                @if($inc->ico_notifiable)
                                    @if($inc->ico_notified_at)
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Notified ({{ $inc->ico_reference_number ?: 'Logged' }})</span>
                                    @else
                                        <span class="badge bg-danger animate__animated animate__pulse animate__infinite">
                                            <i class="fas fa-stopwatch"></i> Due: {{ $inc->getIcoDeadlineHoursRemaining() }}h left
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted small">Below Threshold</span>
                                @endif
                            </td>
                            <td><small>{{ $inc->reporter->username ?? 'System' }}</small></td>
                            <td class="text-end text-right">
                                <a href="{{ route('admin.privacy.incidents.show', $inc->id) }}" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-microscope"></i> Investigate
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-shield-alt fa-3x text-success mb-3 d-block"></i>
                                No security incidents or personal data breaches on record.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Log New Incident -->
<div class="modal fade" id="newIncidentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.privacy.incidents.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle mr-2"></i> Report Security Incident</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body space-y-3">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold small text-uppercase">Incident Title</label>
                        <input type="text" name="title" required placeholder="e.g. Unauthorized access attempt on administrative account" class="form-control">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold small text-uppercase">Severity Classification</label>
                            <select name="severity" class="form-select form-control" required>
                                <option value="low">Low (Negligible impact on individuals)</option>
                                <option value="medium" selected>Medium (Internal anomaly, isolated exposure)</option>
                                <option value="high">High (High risk to rights and freedoms of individuals)</option>
                                <option value="critical">Critical (Systemic breach, exfiltration of sensitive PII)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold small text-uppercase">Detection Date & Time</label>
                            <input type="datetime-local" name="detected_at" value="{{ now()->format('Y-m-d\TH:i') }}" required class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold small text-uppercase">Immediate Containment Actions Taken</label>
                        <textarea name="containment_actions" rows="2" placeholder="e.g. Account credentials revoked, IP blocked on WAF, firewall rule applied..." class="form-control"></textarea>
                    </div>
                    <div class="p-3 bg-light rounded border">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="ico_notifiable" value="1" id="icoNotifiableCheck">
                            <label class="form-check-label font-weight-bold text-danger" for="icoNotifiableCheck">
                                Triggers Mandatory UK ICO Notification (72-Hour Rule)
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">Check if the personal data breach is likely to result in a risk to the rights and freedoms of natural persons.</small>
                        <textarea name="ico_assessment_reasoning" rows="2" placeholder="Reasoning for ICO notification decision..." class="form-control form-control-sm mt-2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger font-weight-bold"><i class="fas fa-save mr-1"></i> Register Incident</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
