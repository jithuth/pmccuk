@extends('layouts.admin')

@section('page_title', 'Event Booking Analytics')

@section('content')
<div class="row">
    <!-- Global Quick Stats -->
    <div class="col-md-3">
        <div class="small-box bg-white shadow-sm border-left-primary">
            <div class="inner">
                <p class="text-muted mb-1 text-uppercase small fw-bold">Total Approved Revenue</p>
                <h3 class="text-primary fw-bold">£{{ number_format($global['total_revenue'], 2) }}</h3>
            </div>
            <div class="icon"><i class="fas fa-pound-sign text-primary opacity-25"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white shadow-sm border-left-success">
            <div class="inner">
                <p class="text-muted mb-1 text-uppercase small fw-bold">Confirmed Attendees</p>
                <h3 class="text-success fw-bold">{{ $global['checked_in'] }}</h3>
            </div>
            <div class="icon"><i class="fas fa-user-check text-success opacity-25"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white shadow-sm border-left-warning">
            <div class="inner">
                <p class="text-muted mb-1 text-uppercase small fw-bold">Pending Pipeline</p>
                <h3 class="text-warning fw-bold">{{ $global['pending_approval'] }}</h3>
            </div>
            <div class="icon"><i class="fas fa-hourglass-start text-warning opacity-25"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-white shadow-sm border-left-info">
            <div class="inner">
                <p class="text-muted mb-1 text-uppercase small fw-bold">Total Bookings Received</p>
                <h3 class="text-info fw-bold">{{ $global['total_bookings'] }}</h3>
            </div>
            <div class="icon"><i class="fas fa-file-invoice text-info opacity-25"></i></div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-white py-3">
        <h3 class="card-title fw-bold text-dark"><i class="fas fa-chart-pie mr-2 text-primary"></i> Event-Wise Booking Summary</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light text-uppercase text-secondary font-weight-bold small">
                    <tr>
                        <th class="ps-4">Event Details</th>
                        <th class="text-center">Total Bookings</th>
                        <th class="text-center">Approved</th>
                        <th class="text-center">Adults</th>
                        <th class="text-center">Kids</th>
                        <th class="text-center">Infants</th>
                        <th class="text-center">Revenue</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats as $s)
                    <tr>
                        <td class="ps-4 pt-3 pb-3">
                            <span class="fw-bold text-dark">{{ $s['event']->title }}</span><br>
                            <small class="text-muted"><i class="fas fa-calendar-day me-1"></i> {{ date('d M Y', strtotime($s['event']->event_date)) }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">{{ $s['total_bookings'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success-soft text-success fw-bold">{{ $s['confirmed_bookings'] }}</span>
                        </td>
                        <td class="text-center fw-bold text-dark">{{ $s['adults'] }}</td>
                        <td class="text-center text-secondary">{{ $s['children'] }}</td>
                        <td class="text-center text-muted">{{ $s['infants'] }}</td>
                        <td class="text-center">
                            <span class="fw-bold text-primary">£{{ number_format($s['total_revenue'], 2) }}</span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('admin.events.bookings') }}?event_id={{ $s['event']->id }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="fas fa-eye me-1"></i> View Guests
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .border-left-primary { border-left: 4px solid #007bff !important; }
    .border-left-success { border-left: 4px solid #28a745 !important; }
    .border-left-warning { border-left: 4px solid #ffc107 !important; }
    .border-left-info { border-left: 4px solid #17a2b8 !important; }
    .bg-success-soft { background-color: rgba(40, 167, 69, 0.1); }
</style>
@endsection
