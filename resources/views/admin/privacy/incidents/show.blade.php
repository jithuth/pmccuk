@extends('layouts.admin')

@section('page_title', 'Incident ' . $incident->reference_number)

@section('content')
<div class="card elevation-2 mb-4 border-top border-4 border-danger">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <span class="badge bg-danger text-white font-monospace fs-6 px-3 py-1 mb-1">
                    {{ $incident->reference_number }}
                </span>
                <span class="badge bg-{{ $incident->severity === 'critical' ? 'danger' : ($incident->severity === 'high' ? 'warning' : 'secondary') }} text-uppercase ms-1">
                    {{ strtoupper($incident->severity) }}
                </span>
                <span class="badge bg-{{ $incident->status === 'closed' ? 'success' : 'primary' }} text-uppercase ms-1">
                    {{ strtoupper($incident->status) }}
                </span>
                <h2 class="h4 font-weight-bold text-dark mt-2 mb-0">{{ $incident->title }}</h2>
                <small class="text-muted">Detected: {{ $incident->detected_at->format('d M Y, h:i A') }} • Reported by: {{ $incident->reporter->username ?? 'System' }}</small>
            </div>

            @if($incident->ico_notifiable)
                <div class="p-3 bg-light border border-danger rounded text-center">
                    <span class="text-danger font-weight-bold d-block text-xs uppercase">UK ICO 72h Notification</span>
                    @if($incident->ico_notified_at)
                        <span class="badge bg-success font-monospace mt-1">Notified (Ref: {{ $incident->ico_reference_number ?: 'Confirmed' }})</span>
                    @else
                        <span class="badge bg-danger font-monospace mt-1">{{ $incident->getIcoDeadlineHoursRemaining() }} hrs remaining</span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Timeline & Containment -->
    <div class="col-lg-8">
        <!-- Chronological Timeline -->
        <div class="card card-outline card-secondary elevation-2 mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-stream mr-2 text-secondary"></i> Incident Timeline & Audit Events
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($incident->timeline as $event)
                        <li class="list-group-item d-flex justify-content-between align-items-start py-3">
                            <div class="space-y-1">
                                <span class="badge bg-dark text-uppercase text-[10px]">{{ $event->event_type }}</span>
                                <div class="font-weight-bold text-dark small mt-1">{{ $event->summary }}</div>
                                <small class="text-muted">Logged by: {{ $event->recorder->username ?? 'Administrator' }}</small>
                            </div>
                            <small class="text-muted text-nowrap">{{ $event->event_timestamp->format('d M Y, h:i A') }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4 small">No timeline events recorded.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card-footer bg-light">
                <form action="{{ route('admin.privacy.incidents.timeline', $incident->id) }}" method="POST" class="row g-2">
                    @csrf
                    <div class="col-md-3">
                        <select name="event_type" class="form-select form-control form-control-sm" required>
                            <option value="containment">Containment Action</option>
                            <option value="investigation">Investigation Finding</option>
                            <option value="notification">Communication / Notice</option>
                            <option value="remediation">Remediation Step</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <input type="text" name="summary" required placeholder="Log timeline update or containment action..." class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-dark w-100 font-weight-bold">
                            <i class="fas fa-plus mr-1"></i> Log
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Containment & Remediation Details -->
        <div class="card card-outline card-primary elevation-2">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-shield-alt mr-2 text-primary"></i> Containment & Remediation Strategy
                </h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong class="small text-uppercase text-muted d-block">Containment Actions:</strong>
                    <p class="text-dark">{{ $incident->containment_actions ?: 'No containment details logged.' }}</p>
                </div>
                <div>
                    <strong class="small text-uppercase text-muted d-block">Remediation & Prevention Measures:</strong>
                    <p class="text-dark">{{ $incident->remediation_steps ?: 'No permanent remediation steps logged yet.' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Update Investigation Status -->
    <div class="col-lg-4">
        <div class="card card-outline card-secondary elevation-2">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-edit mr-2 text-secondary"></i> Update Incident Status
                </h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.privacy.incidents.update', $incident->id) }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Investigation Status</label>
                        <select name="status" class="form-select form-control" required>
                            <option value="open" {{ $incident->status === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="investigating" {{ $incident->status === 'investigating' ? 'selected' : '' }}>Investigating</option>
                            <option value="contained" {{ $incident->status === 'contained' ? 'selected' : '' }}>Contained</option>
                            <option value="remediated" {{ $incident->status === 'remediated' ? 'selected' : '' }}>Remediated</option>
                            <option value="closed" {{ $incident->status === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Lead Investigator</label>
                        <select name="lead_investigator" class="form-select form-control">
                            <option value="">-- Unassigned --</option>
                            @foreach($admins as $adm)
                                <option value="{{ $adm->id }}" {{ $incident->lead_investigator == $adm->id ? 'selected' : '' }}>
                                    {{ $adm->username }} ({{ $adm->role }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded border">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="ico_notifiable" value="1" id="icoCheck" {{ $incident->ico_notifiable ? 'checked' : '' }}>
                            <label class="form-check-label font-weight-bold text-danger" for="icoCheck">
                                ICO 72h Notification Required
                            </label>
                        </div>
                        <input type="text" name="ico_reference_number" value="{{ $incident->ico_reference_number }}" placeholder="ICO Case Reference Number" class="form-control form-control-sm mt-2">
                        <textarea name="ico_assessment_reasoning" rows="2" placeholder="ICO Assessment reasoning..." class="form-control form-control-sm mt-2">{{ $incident->ico_assessment_reasoning }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Containment Notes</label>
                        <textarea name="containment_actions" rows="2" class="form-control">{{ $incident->containment_actions }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small font-weight-bold">Remediation Steps</label>
                        <textarea name="remediation_steps" rows="2" class="form-control">{{ $incident->remediation_steps }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
