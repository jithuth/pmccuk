@extends('layouts.admin')

@section('page_title', 'Edit Attendance Entry')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Modify Booking #{{ $booking->id }}</h3>
            </div>
            <form action="{{ route('admin.events.bookings.update', $booking->id) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label>Attendee Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="{{ $booking->full_name }}" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" value="{{ $booking->email }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>Phone/Mobile</label>
                                <input type="text" name="phone" class="form-control" value="{{ $booking->phone }}" required>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <label class="d-block mb-3 font-weight-bold">Group Composition</label>
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="small">Adults</label>
                                <input type="number" name="adult_count" class="form-control" value="{{ $booking->adult_count }}" min="0">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="small">Children</label>
                                <input type="number" name="child_count" class="form-control" value="{{ $booking->child_count }}" min="0">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="small">Infants</label>
                                <input type="number" name="infant_count" class="form-control" value="{{ $booking->infant_count }}" min="0">
                            </div>
                        </div>
                    </div>
                    <p class="text-muted small mt-2">Note: Changing counts here won't automatically update the "Total Price" in this version.</p>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('admin.events.bookings') }}" class="btn btn-default mr-2">Cancel</a>
                    <button type="submit" class="btn btn-info px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
