@extends('layouts.admin')

@section('page_title', 'Sponsor Offer Redemptions')

@section('content')
<div class="row g-4">
    <!-- Header Card -->
    <div class="col-md-12">
        <div class="card shadow-sm border-0 bg-dark text-white rounded-3">
            <div class="card-body p-4 p-md-5">
                <span class="badge bg-pink text-uppercase font-black tracking-wider mb-2 px-3 py-2">Sponsors & Perks</span>
                <h2 class="fw-black mb-3 text-white">Sponsor Offer Redemptions</h2>
                <p class="text-slate-400 mb-0 leading-relaxed">
                    Track which members have redeemed sponsor offers and view the timestamps and details of code/perk redemptions.
                </p>
            </div>
        </div>
    </div>

    <!-- Redemptions List -->
    <div class="col-md-12">
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase text-xs tracking-wider font-bold text-slate-500">
                            <tr>
                                <th class="ps-3 py-3" style="width: 25%;">Redeemed At</th>
                                <th style="width: 30%;">Member Profile</th>
                                <th style="width: 30%;">Sponsor & Offer Details</th>
                                <th class="pe-3" style="width: 15%;">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @forelse($redemptions as $redemption)
                                <tr>
                                    <td class="ps-3 py-3">
                                        @if($redemption->redeemed_at)
                                            @php
                                                $redeemedAt = ($redemption->redeemed_at instanceof \Carbon\Carbon) ? $redemption->redeemed_at : \Illuminate\Support\Carbon::parse($redemption->redeemed_at);
                                            @endphp
                                            <span class="fw-bold text-slate-800 d-block">{{ $redeemedAt->format('M d, Y') }}</span>
                                            <small class="text-muted text-xs">{{ $redeemedAt->format('H:i:s') }}</small>
                                        @else
                                            <span class="text-muted italic">Unknown</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($redemption->member)
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2 text-xs text-secondary" style="width: 32px; height: 32px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-slate-800 d-block">{{ $redemption->member->full_name }}</span>
                                                    <span class="text-xs text-muted">ID: {{ $redemption->member->membership_no }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-danger italic fw-bold">Removed Member</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($redemption->offer)
                                            <div>
                                                <span class="fw-bold text-slate-800 d-block">{{ $redemption->offer->title }}</span>
                                                <span class="text-xs text-slate-500">Sponsor: <strong>{{ $redemption->offer->sponsor_name }}</strong></span>
                                            </div>
                                        @else
                                            <span class="text-muted italic">Deleted Offer</span>
                                        @endif
                                    </td>
                                    <td class="pe-3">
                                        @php
                                            $status = strtolower($redemption->status ?? 'active');
                                            $badgeClass = 'bg-success-subtle text-success';
                                            if ($status === 'pending') $badgeClass = 'bg-warning-subtle text-warning';
                                            elseif ($status === 'expired' || $status === 'cancelled') $badgeClass = 'bg-danger-subtle text-danger';
                                        @endphp
                                        <span class="badge {{ $badgeClass }} font-bold text-[10px] uppercase px-2.5 py-1.5 rounded">
                                            {{ $status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-gift fa-3x mb-3 text-muted/30"></i>
                                        <p class="mb-0">No sponsor offers have been redeemed yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                @if($redemptions->hasPages())
                    <div class="card-footer bg-white border-0 px-0 pt-4">
                        {{ $redemptions->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
