@extends('layouts.admin')

@section('page_title', 'Dashboard')

@section('content')
@php
    $role = Auth::guard('admin')->user()->role ?? 'admin';
@endphp

@if($role === 'sponsor')
    <!-- SPONSOR DASHBOARD -->
    <div class="row">
        <div class="col-md-4">
            <div class="small-box bg-info text-white p-3 rounded shadow-sm mb-3">
                <div class="inner">
                    <h3>{{ \App\Models\SponsorOffer::where('admin_id', Auth::guard('admin')->id())->count() }}</h3>
                    <p>Active Offers</p>
                </div>
                <div class="icon"><i class="fas fa-gift fa-2x opacity-50"></i></div>
                <a href="{{ route('admin.sponsors.offers') }}" class="small-box-footer d-block text-white mt-2 small">Manage Offers <i class="fas fa-arrow-circle-right ms-1"></i></a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-success text-white p-3 rounded shadow-sm mb-3">
                <div class="inner">
                    <h3>{{ \App\Models\OfferRedemption::whereHas('offer', function($q) { $q->where('admin_id', Auth::guard('admin')->id()); })->count() }}</h3>
                    <p>Total Redemptions</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle fa-2x opacity-50"></i></div>
                <a href="{{ route('admin.sponsors.redemptions') }}" class="small-box-footer d-block text-white mt-2 small">View Logs <i class="fas fa-arrow-circle-right ms-1"></i></a>
            </div>
        </div>
    </div>
@else
    <!-- ADMIN DASHBOARD -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-purple text-white p-3 rounded shadow-sm mb-4" style="background-color: #6f42c1 !important;">
                <div class="inner">
                    <h3>{{ $stats['total_members'] }}</h3>
                    <p>Total Members</p>
                </div>
                <div class="icon"><i class="fas fa-users fa-2x opacity-50"></i></div>
                <a href="{{ route('admin.members.index') }}" class="small-box-footer d-block text-white mt-2 small">More info <i class="fas fa-arrow-circle-right ms-1"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning text-dark p-3 rounded shadow-sm mb-4">
                <div class="inner">
                    <h3>{{ $stats['pending_members'] }}</h3>
                    <p>Pending Members</p>
                </div>
                <div class="icon"><i class="fas fa-user-plus fa-2x opacity-50"></i></div>
                <a href="{{ route('admin.members.new') }}" class="small-box-footer d-block text-dark mt-2 small">More info <i class="fas fa-arrow-circle-right ms-1"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success text-white p-3 rounded shadow-sm mb-4">
                <div class="inner">
                    <h3>{{ \App\Models\Menu::count() }}</h3>
                    <p>Menu Items</p>
                </div>
                <div class="icon"><i class="fas fa-list fa-2x opacity-50"></i></div>
                <a href="{{ route('admin.menus') }}" class="small-box-footer d-block text-white mt-2 small">More info <i class="fas fa-arrow-circle-right ms-1"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger text-white p-3 rounded shadow-sm mb-4">
                <div class="inner">
                    <h3>{{ $stats['new_messages'] }}</h3>
                    <p>Unread Messages</p>
                </div>
                <div class="icon"><i class="fas fa-envelope fa-2x opacity-50"></i></div>
                <a href="{{ route('admin.messages') }}" class="small-box-footer d-block text-white mt-2 small">More info <i class="fas fa-arrow-circle-right ms-1"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0"><i class="fas fa-history me-2 text-primary"></i>Recently Joined Members</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $recentMembers = \App\Models\Member::orderBy('created_at', 'desc')->take(5)->get();
                                @endphp
                                @foreach($recentMembers as $m)
                                <tr>
                                    <td>{{ $m->full_name }}</td>
                                    <td>{{ $m->membership_type }}</td>
                                    <td>
                                        <span class="badge bg-{{ $m->status == 'active' ? 'success' : ($m->status == 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($m->status) }}
                                        </span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($m->created_at)->format('d M Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white text-center">
                    <h5 class="card-title mb-0">System Quick View</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Database Environment:</span>
                        <span class="badge bg-info">Production (Legacy)</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Laravel Framework:</span>
                        <span class="badge bg-primary">{{ app()->version() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Admin UI:</span>
                        <span class="badge bg-dark">AdminLTE 4.0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
