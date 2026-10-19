@extends('layouts.admin')

@section('page_title', 'OTP Request History')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-0">
                <i class="fas fa-paper-plane text-primary mr-2"></i>OTP Request & Delivery History
            </h1>
            <p class="text-muted small mb-0">Track OTP challenges, hash verification status, provider delivery evidence, and attempts.</p>
        </div>
    </div>

    @include('admin.security._nav')

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.security.otp') }}" class="row g-2 align-items-center text-xs">
                <div class="col-md-3">
                    <label class="font-weight-bold text-muted mb-1">Member / Identifier</label>
                    <input type="text" name="identifier" value="{{ request('identifier') }}" placeholder="e.g. PMCC-1001, phone, email" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">Delivery Status</label>
                    <select name="delivery_status" class="form-control form-control-sm">
                        <option value="">All Statuses</option>
                        <option value="confirmed" {{ request('delivery_status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="sent" {{ request('delivery_status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="pending" {{ request('delivery_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ request('delivery_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="font-weight-bold text-muted mb-1">Source IP</label>
                    <input type="text" name="ip" value="{{ request('ip') }}" placeholder="e.g. 192.168.1.1" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold text-muted mb-1">Date Range</label>
                    <div class="d-flex gap-1">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-1 mt-4">
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold w-50">Filter</button>
                    <a href="{{ route('admin.security.otp') }}" class="btn btn-outline-secondary btn-sm w-50">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- OTP Challenges Table -->
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-xs">
                    <thead class="bg-light text-muted uppercase">
                        <tr>
                            <th class="px-3 py-2">Challenge & Time</th>
                            <th class="py-2">Target Member</th>
                            <th class="py-2">Delivery Status & Evidence</th>
                            <th class="py-2">Attempts</th>
                            <th class="py-2">Expiry & Status</th>
                            <th class="py-2">Source IP</th>
                            <th class="py-2 text-right pr-3">Evidence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($challenges as $ch)
                            <tr>
                                <td class="px-3 py-2.5">
                                    <div class="font-mono font-weight-bold text-dark">{{ substr($ch->challenge_uuid, 0, 8) }}...</div>
                                    <div class="text-muted text-[10px]">{{ $ch->created_at->format('Y-m-d H:i:s') }} UTC</div>
                                </td>
                                <td class="py-2.5">
                                    <div class="font-weight-bold text-dark">{{ $ch->identifier }}</div>
                                    @if($ch->member)
                                        <div class="text-muted text-[10px]">{{ $ch->member->full_name }} (ID: {{ $ch->member->id }})</div>
                                    @else
                                        <span class="badge badge-light border text-[10px]">Unmatched Identifier</span>
                                    @endif
                                </td>
                                <td class="py-2.5">
                                    <span class="badge badge-{{ $ch->delivery_status === 'confirmed' ? 'success' : ($ch->delivery_status === 'failed' ? 'danger' : 'warning') }} px-2 py-0.5">
                                        {{ strtoupper($ch->delivery_status) }}
                                    </span>
                                    <div class="text-muted text-[10px] mt-0.5">
                                        @if(!empty($ch->whatsapp_delivery_evidence['message_id']))
                                            <i class="fab fa-whatsapp text-success"></i> Msg ID: {{ substr($ch->whatsapp_delivery_evidence['message_id'], 0, 10) }}...
                                        @elseif(!empty($ch->email_delivery_evidence))
                                            <i class="fas fa-envelope text-primary"></i> Dispatched via SMTP
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2.5">
                                    <span class="font-weight-bold {{ $ch->attempts >= $ch->max_attempts ? 'text-danger' : 'text-dark' }}">
                                        {{ $ch->attempts }} / {{ $ch->max_attempts }}
                                    </span>
                                </td>
                                <td class="py-2.5">
                                    @if($ch->consumed_at)
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Verified & Consumed</span>
                                        <div class="text-[10px] text-muted">{{ $ch->consumed_at->format('H:i:s') }}</div>
                                    @elseif($ch->isExpired())
                                        <span class="badge badge-secondary"><i class="fas fa-hourglass-end mr-1"></i> Expired</span>
                                    @else
                                        <span class="badge badge-info"><i class="fas fa-clock mr-1"></i> Valid (Expires in {{ now()->diffInMinutes($ch->expires_at) }}m)</span>
                                    @endif
                                </td>
                                <td class="py-2.5">
                                    <a href="{{ route('admin.security.ip', ['ip' => $ch->ip_address]) }}" class="font-mono text-primary font-weight-bold">
                                        {{ $ch->ip_address }}
                                    </a>
                                </td>
                                <td class="py-2.5 text-right pr-3">
                                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick='showOtpModal(@json($ch))'>
                                        <i class="fas fa-file-alt"></i> Details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No OTP challenge records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <div class="text-xs text-muted">Showing {{ $challenges->firstItem() ?? 0 }} to {{ $challenges->lastItem() ?? 0 }} of {{ $challenges->total() }} records</div>
                {{ $challenges->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal: OTP Challenge Details -->
<div class="modal fade" id="otpModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="otpModalTitle">OTP Challenge Evidence</h5>
                <button type="button" class="close" data-dismiss="modal" onclick="$('#otpModal').modal('hide')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <p class="text-xs text-muted mb-2">Note: Raw OTP values are never stored or logged in accordance with security policy. The code was stored as a cryptographic one-way hash.</p>
                <pre id="otpModalJson" class="bg-dark text-light p-3 rounded" style="max-height:450px;overflow-y:auto;font-size:12px;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function showOtpModal(ch) {
    document.getElementById('otpModalTitle').innerText = 'Challenge: ' + ch.challenge_uuid;
    document.getElementById('otpModalJson').innerText = JSON.stringify(ch, null, 2);
    $('#otpModal').modal('show');
}
</script>
@endsection
