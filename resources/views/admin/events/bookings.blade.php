@extends('layouts.admin')

@section('page_title', 'Event Attendance Logs')

@section('content')
<div class="row">
    <div class="col-12">

        {{-- Summary Stats --}}
        @php
            $totalBookings = $bookings->total();
            $checkedIn = $bookings->getCollection()->filter(fn($b) => !is_null($b->check_in_at))->count();
        @endphp
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="small-box bg-info">
                    <div class="inner"><h3>{{ $bookings->total() }}</h3><p>Total Bookings</p></div>
                    <div class="icon"><i class="fas fa-ticket-alt"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-success">
                    <div class="inner"><h3>{{ $checkedIn }}</h3><p>Checked In (this page)</p></div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-warning">
                    <div class="inner"><h3>{{ $bookings->getCollection()->filter(fn($b) => $b->booking_status == 'pending')->count() }}</h3><p>Pending Approval</p></div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-danger">
                    <div class="inner"><h3>{{ $bookings->getCollection()->filter(fn($b) => is_null($b->check_in_at) && in_array($b->booking_status, ['confirmed','approved']))->count() }}</h3><p>Not Yet Arrived</p></div>
                    <div class="icon"><i class="fas fa-user-times"></i></div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i> Filter Attendance</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Event</label>
                        <select name="event_id" class="form-select">
                            <option value="">All Events</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                    {{ $event->title }} ({{ date('M d, Y', strtotime($event->event_date)) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Booking Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Check-In Status</label>
                        <select name="check_in" class="form-select">
                            <option value="">All</option>
                            <option value="checked_in" {{ request('check_in') == 'checked_in' ? 'selected' : '' }}>Attended</option>
                            <option value="not_checked" {{ request('check_in') == 'not_checked' ? 'selected' : '' }}>Not Arrived</option>
                        </select>
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <a href="{{ route('admin.events.bookings') }}" class="btn btn-default w-100">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ul mr-2"></i> Attendance Listing</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Event</th>
                                <th>Attendee</th>
                                <th>Booking Status</th>
                                <th>Group</th>
                                <th>Amount</th>
                                <th>Check-In</th>
                                <th>Approved By</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $booking)
                            <tr class="{{ $booking->check_in_at ? 'table-success' : '' }}">
                                <td><small class="text-muted">#{{ $booking->id }}</small></td>
                                <td>
                                    <span class="fw-bold small">{{ $booking->event->title ?? 'N/A' }}</span><br>
                                    <small class="text-muted">{{ date('M d, Y', strtotime($booking->event->event_date ?? '')) }}</small>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ $booking->full_name }}</span><br>
                                    <small class="text-muted">{{ $booking->email }}</small><br>
                                    <small class="badge bg-secondary">{{ $booking->membership_no }}</small>
                                </td>
                                <td>
                                    @if($booking->booking_status == 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif(in_array($booking->booking_status, ['confirmed','approved']))
                                        <span class="badge bg-success">Confirmed</span>
                                    @else
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $booking->adult_count }}A · {{ $booking->child_count }}C · {{ $booking->infant_count }}I</small>
                                    <br><span class="fw-bold text-primary small">£{{ number_format($booking->total_amount, 2) }}</span>
                                </td>
                                <td><span class="fw-bold text-primary">£{{ number_format($booking->total_amount, 2) }}</span></td>
                                <td>
                                    @if($booking->check_in_at)
                                        <span class="badge bg-success mb-1"><i class="fas fa-check mr-1"></i> Attended</span><br>
                                        <small class="text-success fw-bold">{{ \Carbon\Carbon::parse($booking->check_in_at)->format('d M Y') }}</small><br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($booking->check_in_at)->format('H:i:s') }}</small>
                                    @else
                                        <span class="badge bg-secondary">Not Arrived</span>
                                    @endif
                                </td>
                                <td>
                                    @if($booking->check_in_at && $booking->checker)
                                        <small class="fw-bold text-dark">{{ $booking->checker->username ?? $booking->checker->email }}</small><br>
                                        <small class="text-muted">Counter Staff</small>
                                    @elseif($booking->check_in_at)
                                        <small class="text-muted">Admin</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        @if(!in_array($booking->booking_status, ['confirmed','approved']))
                                        <a href="{{ route('admin.events.bookings.status', [$booking->id, 'confirmed']) }}" class="btn btn-sm btn-outline-success" title="Approve & Send Ticket">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        @else
                                        <a href="{{ route('admin.events.bookings.resend', $booking->id) }}" class="btn btn-sm btn-outline-info" title="Resend Ticket">
                                            <i class="fas fa-sync-alt"></i>
                                        </a>
                                        @endif

                                        <a href="{{ route('admin.events.bookings.edit', $booking->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Details">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        @if($booking->booking_status != 'rejected')
                                        <a href="{{ route('admin.events.bookings.status', [$booking->id, 'rejected']) }}" class="btn btn-sm btn-outline-danger" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="deleteBooking({{ $booking->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-form-{{ $booking->id }}" action="{{ route('admin.events.bookings.delete', $booking->id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">No bookings found matching your criteria.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                {{ $bookings->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<script>
    function deleteBooking(id) {
        if (confirm('Are you sure you want to delete this booking entry? This action cannot be undone.')) {
            document.getElementById('delete-form-' + id).submit();
        }
    }
</script>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Filter Card -->
        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i> Filter Bookings</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Event</label>
                        <select name="event_id" class="form-select">
                            <option value="">All Events</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                    {{ $event->title }} ({{ date('M d, Y', strtotime($event->event_date)) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <a href="{{ route('admin.events.bookings') }}" class="btn btn-default w-100">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Attendance Listing</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Event</th>
                                <th>Attendee</th>
                                <th>Status</th>
                                <th>Counts</th>
                                <th>Total</th>
                                <th>Contact</th>
                                <th>Booking Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $booking)
                            <tr>
                                <td>#{{ $booking->id }}</td>
                                <td>
                                    <span class="fw-bold">{{ $booking->event->title ?? 'N/A' }}</span><br>
                                    <small class="text-muted">{{ date('M d, Y', strtotime($booking->event->event_date ?? '')) }}</small>
                                </td>
                                <td>
                                    <span class="fw-bold">{{ $booking->full_name }}</span><br>
                                    <small class="badge bg-secondary">{{ $booking->membership_no }}</small>
                                </td>
                                <td>
                                    @if($booking->booking_status == 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif($booking->booking_status == 'confirmed' || $booking->booking_status == 'approved')
                                        <span class="badge bg-success">Confirmed</span>
                                    @else
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    <small>A: {{ $booking->adult_count }} | C: {{ $booking->child_count }} | I: {{ $booking->infant_count }}</small>
                                </td>
                                <td><span class="fw-bold text-primary">£{{ number_format($booking->total_amount, 2) }}</span></td>
                                <td>
                                    <small>{{ $booking->email }}</small><br>
                                    <small>{{ $booking->phone }}</small>
                                </td>
                                <td>{{ date('M d, Y H:i', strtotime($booking->created_at)) }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        @if($booking->booking_status != 'confirmed' && $booking->booking_status != 'approved')
                                        <a href="{{ route('admin.events.bookings.status', [$booking->id, 'confirmed']) }}" class="btn btn-sm btn-outline-success" title="Approve & Send Ticket">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        @else
                                        <a href="{{ route('admin.events.bookings.resend', $booking->id) }}" class="btn btn-sm btn-outline-info" title="Resend Ticket">
                                            <i class="fas fa-sync-alt"></i>
                                        </a>
                                        @endif
                                        
                                        <a href="{{ route('admin.events.bookings.edit', $booking->id) }}" class="btn btn-sm btn-outline-primary" title="Edit Details">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        @if($booking->booking_status != 'rejected')
                                        <a href="{{ route('admin.events.bookings.status', [$booking->id, 'rejected']) }}" class="btn btn-sm btn-outline-danger" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="deleteBooking({{ $booking->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-form-{{ $booking->id }}" action="{{ route('admin.events.bookings.delete', $booking->id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">No bookings found matching your criteria.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                {{ $bookings->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</div>

<script>
    function deleteBooking(id) {
        if (confirm('Are you sure you want to delete this booking entry? This action cannot be undone.')) {
            document.getElementById('delete-form-' + id).submit();
        }
    }
</script>
@endsection
