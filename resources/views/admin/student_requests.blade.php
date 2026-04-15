@extends('layouts.admin')

@section('page_title', 'Student Recruitment & inquiries')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0 text-primary">Incoming Student Requests</h5>
                <span class="badge bg-primary rounded-pill px-3">{{ $requests->total() }} Total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4 border-0">Student Name</th>
                                <th class="border-0">Contact Details</th>
                                <th class="border-0">University/Year</th>
                                <th class="border-0">Assigned To</th>
                                <th class="border-0">Status</th>
                                <th class="text-end pe-4 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $r)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $r->full_name }}</div>
                                    <div class="text-xs text-muted">ID: #STU-{{ $r->id }}</div>
                                </td>
                                <td>
                                    <div class="small"><i class="fas fa-envelope me-1 text-muted"></i> {{ $r->email }}</div>
                                    <div class="small"><i class="fas fa-phone me-1 text-muted"></i> {{ $r->phone }}</div>
                                </td>
                                <td>
                                    <div class="small fw-bold">{{ $r->university }}</div>
                                    <div class="text-xs text-primary">{{ $r->study_year }}</div>
                                </td>
                                <td>
                                    <span class="text-xs font-medium text-muted">None Assigned</span>
                                </td>
                                <td>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 rounded-pill small fw-bold">NEW INQUIRY</span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary rounded-3 me-2"><i class="fas fa-eye"></i></button>
                                        <button class="btn btn-sm btn-outline-success rounded-3 me-2"><i class="fas fa-check"></i></button>
                                        <button class="btn btn-sm btn-outline-danger rounded-3"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted italic">
                                    <i class="fas fa-user-graduate fa-3x mb-3 d-block opacity-25"></i>
                                    No student requests found in the database.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 py-3">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
