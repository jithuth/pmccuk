@extends('layouts.admin')

@section('page_title', 'Team & Committee Management')

@section('content')
<div class="row">
    <!-- MEMBER FORM -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header {{ $editMember ? 'bg-info' : 'bg-primary' }} text-white border-0 py-3">
                <h5 class="card-title fw-bold mb-0">
                    <i class="fas {{ $editMember ? 'fa-user-edit' : 'fa-user-plus' }} me-1"></i> 
                    {{ $editMember ? 'Edit Member' : 'Add New Member' }}
                </h5>
            </div>
            <form action="{{ $editMember ? route('admin.team.update', $editMember->id) : route('admin.team.add') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Full Name</label>
                        <input type="text" name="name" class="form-control" required value="{{ $editMember ? $editMember->name : '' }}" placeholder="John Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Role</label>
                        <input type="text" name="role" class="form-control" required value="{{ $editMember ? $editMember->role : '' }}" placeholder="e.g. Secretary">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Service Years</label>
                        <input type="text" name="service_years" class="form-control" value="{{ $editMember ? $editMember->service_years : '' }}" placeholder="e.g. 2023-2025">
                        <small class="text-muted">For former members.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Short Bio</label>
                        <textarea name="description" class="form-control" rows="3">{{ $editMember ? $editMember->description : '' }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Photo</label>
                        <input type="file" name="team_image" class="form-control mb-1">
                        <input type="text" name="image_url" class="form-control form-control-sm" placeholder="Or Image URL" value="{{ $editMember ? $editMember->image_url : '' }}">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Facebook</label>
                            <input type="url" name="facebook_url" class="form-control" value="{{ $editMember ? $editMember->facebook_url : '' }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">LinkedIn</label>
                            <input type="url" name="linkedin_url" class="form-control" value="{{ $editMember ? $editMember->linkedin_url : '' }}">
                        </div>
                    </div>
                    <div class="row g-2 mb-0">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Category</label>
                            <select name="category" class="form-select">
                                <option value="current" {{ ($editMember && $editMember->category == 'current') ? 'selected' : '' }}>Current</option>
                                <option value="former" {{ ($editMember && $editMember->category == 'former') ? 'selected' : '' }}>Former</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Order</label>
                            <input type="number" name="order_no" class="form-control" value="{{ $editMember ? $editMember->order_no : '0' }}">
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 p-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm rounded-pill">
                        <i class="fas fa-save me-2"></i> {{ $editMember ? 'UPDATE MEMBER' : 'ADD TO COMMITTEE' }}
                    </button>
                    @if($editMember)
                        <a href="{{ route('admin.team') }}" class="btn btn-light w-100 mt-2 rounded-pill">CANCEL</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- MEMBERS LIST -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title fw-bold mb-0">Current Executive Committee</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-3 border-0">Photo</th>
                                <th class="border-0">Name & Role</th>
                                <th class="border-0">Socials</th>
                                <th class="text-end pe-3 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($currentTeam as $m)
                            <tr>
                                <td class="ps-3" style="width: 80px;">
                                    <div class="rounded-circle overflow-hidden shadow-sm border" style="width: 50px; height: 50px;">
                                        <img src="{{ !empty($m->image_url) ? (str_starts_with($m->image_url, 'http') ? $m->image_url : asset('storage/'.$m->image_url)) : 'https://placehold.co/200x200?text=User' }}" class="w-100 h-100" style="object-fit: cover;">
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $m->name }}</div>
                                    <div class="text-primary small fw-bold text-uppercase">{{ $m->role }}</div>
                                </td>
                                <td>
                                    @if($m->facebook_url)<a href="{{ $m->facebook_url }}" target="_blank" class="text-primary me-2"><i class="fab fa-facebook fa-lg"></i></a>@endif
                                    @if($m->linkedin_url)<a href="{{ $m->linkedin_url }}" target="_blank" class="text-info"><i class="fab fa-linkedin fa-lg"></i></a>@endif
                                    @if(!$m->facebook_url && !$m->linkedin_url)<span class="text-muted small">None</span>@endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.team', ['edit' => $m->id]) }}" class="btn btn-sm btn-outline-info rounded-3 me-1"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('admin.team.delete', $m->id) }}" method="POST" onsubmit="return confirm('Remove this member?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No current members.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="card-title fw-bold mb-0 text-muted">Former Committees</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="bg-light small text-uppercase text-secondary">
                        <tr>
                            <th class="ps-3">Name & Role</th>
                            <th>Years</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($formerTeam as $m)
                        <tr>
                            <td class="ps-3 py-2">
                                <div class="fw-bold">{{ $m->name }}</div>
                                <small class="text-muted">{{ $m->role }}</small>
                            </td>
                            <td><span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2">{{ $m->service_years ?? '-' }}</span></td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.team', ['edit' => $m->id]) }}" class="btn btn-link btn-sm text-info text-decoration-none py-0">Edit</a>
                                <form action="{{ route('admin.team.delete', $m->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-link btn-sm text-danger text-decoration-none py-0" onclick="return confirm('Delete?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
