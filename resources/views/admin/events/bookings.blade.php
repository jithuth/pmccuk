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

            {{-- Catering & Headcount Breakdown Analytics --}}
            <div class="card card-outline card-info mb-4 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-info fw-bold mb-0"><i class="fas fa-utensils me-2"></i> Headcount & Catering Analytics</h3>
                    <span class="badge bg-info text-white font-weight-bold">Active Filter Summary</span>
                </div>
                <div class="card-body py-3">
                    <div class="row text-center g-2">
                        <div class="col-md-2 col-4">
                            <div class="p-2 border rounded bg-light">
                                <small class="text-uppercase text-muted d-block fw-bold" style="font-size: 10px;">Total Heads</small>
                                <span class="h5 mb-0 fw-bold text-dark">{{ $summary['total_heads'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="col-md-2 col-4">
                            <div class="p-2 border rounded bg-light border-dark">
                                <small class="text-uppercase text-muted d-block fw-bold" style="font-size: 10px;">Adults (A)</small>
                                <span class="h5 mb-0 fw-bold text-dark">{{ $summary['adults'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="col-md-2 col-4">
                            <div class="p-2 border rounded bg-light border-primary">
                                <small class="text-uppercase text-primary d-block fw-bold" style="font-size: 10px;">Children (C)</small>
                                <span class="h5 mb-0 fw-bold text-primary">{{ $summary['children'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="col-md-2 col-4">
                            <div class="p-2 border rounded bg-warning bg-opacity-10 border-warning">
                                <small class="text-uppercase text-warning d-block fw-bold" style="font-size: 10px;">Students (S)</small>
                                <span class="h5 mb-0 fw-bold text-dark">{{ $summary['students'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="col-md-2 col-4">
                            <div class="p-2 border rounded bg-light border-info">
                                <small class="text-uppercase text-info d-block fw-bold" style="font-size: 10px;">Infants (I)</small>
                                <span class="h5 mb-0 fw-bold text-info">{{ $summary['infants'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="col-md-2 col-4">
                            <div class="p-2 border rounded bg-success bg-opacity-10 border-success">
                                <small class="text-uppercase text-success d-block fw-bold" style="font-size: 10px;">Scanned Entry</small>
                                <span class="h5 mb-0 fw-bold text-success">{{ $summary['checked_in_heads'] ?? 0 }} / {{ $summary['total_heads'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card card-outline card-primary mb-4 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-primary fw-bold mb-0"><i class="fas fa-search me-1"></i> Search & Filter Attendance</h3>
                    @if(request('search') || request('event_id') || (request('status') && request('status') !== 'pending_and_approved') || request('check_in') || request('ticket_type'))
                        <span class="badge bg-warning text-dark font-weight-bold"><i class="fas fa-filter me-1"></i> Active Filters</span>
                    @endif
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-dark"><i class="fas fa-user-search text-primary me-1"></i> Search Attendee</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" id="attendee-search-input" class="form-control form-control-sm"
                                       placeholder="Name, email, phone, membership #, ID..."
                                       value="{{ request('search') }}">
                                @if(request('search'))
                                    <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="btn btn-outline-secondary btn-sm" title="Clear Search">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
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
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Booking Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="pending_and_approved" {{ (request('status') == 'pending_and_approved' || !request()->has('status')) ? 'selected' : '' }}>Pending & Approved</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved Only</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending Only</option>
                                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses (inc. Rejected)</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected Only</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Check-In</label>
                            <select name="check_in" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="checked_in" {{ request('check_in') == 'checked_in' ? 'selected' : '' }}>
                                    Attended</option>
                                <option value="not_checked" {{ request('check_in') == 'not_checked' ? 'selected' : '' }}>Not
                                    Arrived</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Ticket Category</label>
                            <select name="ticket_type" class="form-select form-select-sm">
                                <option value="">All Ticket Types</option>
                                <option value="student" {{ request('ticket_type') == 'student' ? 'selected' : '' }}>🎓 Student Pass Only</option>
                                <option value="adult" {{ request('ticket_type') == 'adult' ? 'selected' : '' }}>Adult Tickets</option>
                                <option value="child" {{ request('ticket_type') == 'child' ? 'selected' : '' }}>Child Tickets</option>
                                <option value="infant" {{ request('ticket_type') == 'infant' ? 'selected' : '' }}>Infant Tickets</option>
                            </select>
                        </div>
                        <div class="col-12 text-end pt-1">
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="submit" class="btn btn-primary btn-sm px-4 font-weight-bold">
                                    <i class="fas fa-search me-1"></i> Search & Apply Filters
                                </button>
                                <a href="{{ route('admin.events.bookings') }}" class="btn btn-outline-secondary btn-sm px-3">
                                    <i class="fas fa-undo me-1"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title text-dark fw-bold">Attendance Listing</h3>
                    <div class="card-tools d-flex gap-2">
                        <a href="{{ route('admin.events.bookings.export_csv', request()->query()) }}"
                            class="btn btn-sm btn-outline-success px-3 rounded-pill fw-bold shadow-sm">
                            <i class="fas fa-file-csv me-1"></i> EXPORT CSV
                        </a>
                        <a href="{{ route('admin.events.bookings.export', request()->query()) }}"
                            class="btn btn-sm btn-success px-3 rounded-pill fw-bold shadow-sm">
                            <i class="fas fa-file-pdf me-1"></i> EXPORT PDF
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
                                            @if($booking->student_doc_path)
                                                <br><a href="{{ asset('storage/' . $booking->student_doc_path) }}" target="_blank" class="badge bg-info text-decoration-none mt-1 shadow-sm"><i class="fas fa-id-card me-1"></i> View Student ID</a>
                                            @endif
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
                                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                                <span class="badge bg-dark" title="Adults">A: {{ $booking->adult_count }}</span>
                                                @if($booking->child_count > 0)
                                                    <span class="badge bg-primary" title="Children">C: {{ $booking->child_count }}</span>
                                                @else
                                                    <span class="badge bg-light text-muted border">C: 0</span>
                                                @endif
                                                @if($booking->student_count > 0)
                                                    <span class="badge bg-warning text-dark fw-bold" title="Students"><i class="fas fa-graduation-cap me-1"></i>S: {{ $booking->student_count }}</span>
                                                @else
                                                    <span class="badge bg-light text-muted border">S: 0</span>
                                                @endif
                                                @if($booking->infant_count > 0)
                                                    <span class="badge bg-info text-white" title="Infants">I: {{ $booking->infant_count }}</span>
                                                @else
                                                    <span class="badge bg-light text-muted border">I: 0</span>
                                                @endif
                                            </div>
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

        // Live client-side instant filter on keypress
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('attendee-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase().trim();
                    const tableRows = document.querySelectorAll('table tbody tr');

                    tableRows.forEach(row => {
                        if (row.cells.length <= 1) return;
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(query) ? '' : 'none';
                    });
                });
            }
        });
    </script>
@endsection