@extends('layouts.admin')

@section('page_title', 'Data Retention & Anonymization Engine')

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <p class="text-muted small">
            Under UK GDPR Article 5(1)(e) (Storage Limitation), personal data must not be kept longer than necessary for its specified lawful purpose. Below are PMCC-UK's active statutory and operational retention rules, with legal holds for mandatory accounting ledgers.
        </p>
    </div>
</div>

<div class="card card-outline card-primary elevation-2 mb-4">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-database text-primary mr-2"></i> Configured Data Retention Policies
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark text-uppercase small">
                    <tr>
                        <th>Category</th>
                        <th>Lawful Basis</th>
                        <th>Period</th>
                        <th>Trigger</th>
                        <th>Action</th>
                        <th>Legal Hold</th>
                        <th class="text-end text-right">Execute Sweep</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($policies as $pol)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ ucwords(str_replace('_', ' ', $pol->data_category)) }}</strong>
                                <small class="text-muted d-block">{{ $pol->purpose }}</small>
                            </td>
                            <td><small class="text-muted">{{ $pol->lawful_basis }}</small></td>
                            <td>
                                <span class="badge bg-light border text-dark">
                                    {{ $pol->retention_period_days }} days ({{ round($pol->retention_period_days / 365, 1) }} yrs)
                                </span>
                            </td>
                            <td><small class="text-muted">{{ $pol->retention_trigger }}</small></td>
                            <td><span class="badge bg-info">{{ strtoupper($pol->disposal_action) }}</span></td>
                            <td>
                                @if($pol->legal_hold)
                                    <span class="badge bg-danger" title="{{ $pol->legal_hold_reason }}">
                                        <i class="fas fa-lock"></i> Legal Hold Active
                                    </span>
                                @else
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Standard</span>
                                @endif
                            </td>
                            <td class="text-end text-right">
                                @if(!$pol->legal_hold)
                                    <form action="{{ route('admin.privacy.retention.execute', $pol->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Execute automated disposal/anonymization sweep for {{ $pol->data_category }}?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-play mr-1"></i> Run Sweep
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small fst-italic">Protected by HMRC Hold</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Retention Execution History -->
<div class="card card-outline card-secondary elevation-2">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-history text-secondary mr-2"></i> Retention Sweep Execution History
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="bg-light small text-uppercase">
                    <tr>
                        <th>Execution Date</th>
                        <th>Policy Category</th>
                        <th>Evaluated</th>
                        <th>Disposed / Anonymized</th>
                        <th>Summary</th>
                        <th>Executor</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $allLogs = \App\Models\DataRetentionLog::with(['policy', 'executor'])->orderBy('created_at', 'desc')->limit(10)->get();
                    @endphp
                    @forelse($allLogs as $log)
                        <tr>
                            <td><small>{{ $log->created_at->format('d M Y, h:i A') }}</small></td>
                            <td class="font-weight-bold">{{ $log->policy->data_category ?? 'General' }}</td>
                            <td>{{ $log->records_evaluated }}</td>
                            <td><span class="badge bg-success">{{ $log->records_disposed }}</span></td>
                            <td><small class="text-muted">{{ $log->summary }}</small></td>
                            <td><small>{{ $log->executor->username ?? 'Automated' }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted small">No retention sweeps have been executed yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
