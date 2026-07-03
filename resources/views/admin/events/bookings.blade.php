@extends('layouts.admin')

@section('page_title', 'Event Attendance Logs')

@section('content')
    <div class="row">
        <div class="col-12">

            {{-- Summary Stats --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="small-box bg-info shadow-sm">
                        <div class="inner">
                            <h3>{{ $bookings->total() }}</h3>
                            <p>Total Bookings</p>
                        </div>
                        <div class="icon"><i class="fas fa-ticket-alt"></i></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="small-box bg-warning shadow-sm">
                        <div class="inner">
                            <h3>{{ \App\Models\EventBooking::where('booking_status', 'pending')->count() }}</h3>
                            <p>Pending Approval</p>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="small-box bg-success shadow-sm">
                        <div class="inner">
                            <h3>{{ \App\Models\EventBooking::where('booking_status', 'approved')->count() }}</h3>
                            <p>Confirmed / Approved</p>
                        </div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="small-box bg-primary shadow-sm">
                        <div class="inner">
                            <h3>{{ \App\Models\EventBooking::whereNotNull('check_in_at')->count() }}</h3>
                            <p>Checked In</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card card-outline card-primary mb-4 shadow-sm">
                <div class="card-header">
                    <h3 class="card-title text-primary fw-bold"><i class="fas fa-filter mr-2"></i> Filter Attendance</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Event</label>
                            <select name="event_id" class="form-select form-select-sm">
                                <option value="">All Events</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                        {{ $event->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Booking Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending
                                </option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved
                                </option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Check-In</label>
                            <select name="check_in" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="checked_in" {{ request('check_in') == 'checked_in' ? 'selected' : '' }}>
                                    Attended</option>
                                <option value="not_checked" {{ request('check_in') == 'not_checked' ? 'selected' : '' }}>Not
                                    Arrived</option>
                            </select>
                        </div>
                        <div class="col-md-3 align-self-end">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Apply Filters</button>
                                <a href="{{ route('admin.events.bookings') }}"
                                    class="btn btn-outline-secondary btn-sm">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-dark fw-bold">Attendance Listing</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.events.bookings.export', request()->query()) }}"
                            class="btn btn-sm btn-success px-3 rounded-pill fw-bold shadow-sm">
                            <i class="fas fa-file-pdf me-1"></i> EXPORT ATTENDEE LIST (PDF)
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="bg-light text-uppercase text-secondary small font-weight-bold">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Event</th>
                                    <th>Attendee</th>
                                    <th>Status</th>
                                    <th>Breakdown</th>
                                    <th>Total</th>
                                    <th>Check-In</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookings as $booking)
                                    <tr class="{{ $booking->check_in_at ? 'table-success' : '' }}">
                                        <td class="ps-3"><small class="text-muted">#{{ $booking->id }}</small></td>
                                        <td>
                                            <span class="fw-bold small">{{ $booking->event->title ?? 'N/A' }}</span><br>
                                            <small
                                                class="text-muted">{{ date('M d, Y', strtotime($booking->event->event_date ?? '')) }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark">{{ $booking->full_name }}</span><br>
                                            <small class="text-muted"><i class="fas fa-envelope me-1"></i>
                                                {{ $booking->email }}</small><br>
                                            <small
                                                class="badge bg-secondary rounded-pill px-2">{{ $booking->membership_no }}</small>
                                        </td>
                                        <td>
                                            @if($booking->booking_status == 'pending')
                                                <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>
                                                    Pending</span>
                                            @elseif($booking->booking_status == 'approved')
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>
                                                    Approved</span>
                                            @else
                                                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>
                                                    Rejected</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="fw-bold text-dark">A: {{ $booking->adult_count }} | C:
                                                {{ $booking->child_count }} | I: {{ $booking->infant_count }}</small>
                                        </td>
                                        <td><span
                                                class="fw-bold text-primary">£{{ number_format($booking->total_amount, 2) }}</span>
                                        </td>
                                        <td>
                                            @if($booking->check_in_at)
                                                <span class="badge bg-success bg-opacity-75 mb-1 px-2"><i
                                                        class="fas fa-user-check mr-1"></i> In</span><br>
                                                <small
                                                    class="text-success fw-bold">{{ \Carbon\Carbon::parse($booking->check_in_at)->format('H:i d M') }}</small>
                                            @else
                                                <span class="text-muted small">Not Checked In</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group">
                                                @if($booking->booking_status == 'pending')
                                                    <a href="{{ route('admin.events.bookings.status', [$booking->id, 'approved']) }}"
                                                        class="btn btn-xs btn-success me-1" title="Approve & Send Ticket">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="{{ route('admin.events.bookings.status', [$booking->id, 'rejected']) }}"
                                                        class="btn btn-xs btn-danger me-1" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                @elseif($booking->booking_status == 'approved')
                                                    <a href="{{ route('admin.events.bookings.resend', $booking->id) }}"
                                                        class="btn btn-xs btn-info me-1" title="Resend Ticket Email">
                                                        <i class="fas fa-envelope"></i>
                                                    </a>
                                                @endif

                                                <a href="{{ route('admin.events.bookings.edit', $booking->id) }}"
                                                    class="btn btn-xs btn-primary me-1" title="Edit Booking">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <button type="button" class="btn btn-xs btn-outline-dark"
                                                    onclick="deleteBooking({{ $booking->id }})" title="Delete entry">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <form id="delete-form-{{ $booking->id }}"
                                                action="{{ route('admin.events.bookings.delete', $booking->id) }}" method="POST"
                                                style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">No bookings found matching your
                                            criteria.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0">
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