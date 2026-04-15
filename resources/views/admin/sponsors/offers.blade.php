@extends('layouts.admin')

@section('page_title', 'Sponsor Offers Manager')

@section('content')
<div class="row">
    <!-- OFFER FORM -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header {{ $editOffer ? 'bg-info' : 'bg-primary' }} text-white border-0 py-3">
                <h5 class="card-title fw-bold mb-0">
                    <i class="fas {{ $editOffer ? 'fa-edit' : 'fa-plus-circle' }} me-1"></i> 
                    {{ $editOffer ? 'Edit Offer' : 'Create New Offer' }}
                </h5>
            </div>
            <form action="{{ $editOffer ? route('admin.sponsors.offers.update', $editOffer->id) : route('admin.sponsors.offers.add') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Sponsor Name</label>
                        <input type="text" name="sponsor_name" class="form-control" required value="{{ $editOffer ? $editOffer->sponsor_name : '' }}" placeholder="e.g. British Airways">
                    </div>
                    
                    @if(Auth::guard('admin')->user()->role === 'admin')
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-primary">Account Owner</label>
                        <select name="admin_id" class="form-select border-primary shadow-none">
                            @foreach($sponsors as $acc)
                                <option value="{{ $acc->id }}" {{ ($editOffer && $editOffer->admin_id == $acc->id) ? 'selected' : '' }}>
                                    {{ $acc->username }} ({{ $acc->role }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                        <input type="hidden" name="admin_id" value="{{ Auth::guard('admin')->id() }}">
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Offer Title</label>
                        <input type="text" name="title" class="form-control" required value="{{ $editOffer ? $editOffer->title : '' }}" placeholder="e.g. 10% Discount">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Logo</label>
                        <input type="file" name="logo_image" class="form-control mb-1">
                        <input type="text" name="logo_url" class="form-control form-control-sm" placeholder="Or Logo URL" value="{{ $editOffer ? $editOffer->logo_url : '' }}">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-bold small text-uppercase">Order</label>
                            <input type="number" name="order_no" class="form-control" value="{{ $editOffer ? $editOffer->order_no : '0' }}">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold small text-uppercase">Type</label>
                            <select name="is_premium" class="form-select">
                                <option value="0" {{ ($editOffer && !$editOffer->is_premium) ? 'selected' : '' }}>Basic</option>
                                <option value="1" {{ ($editOffer && $editOffer->is_premium) ? 'selected' : '' }}>Premium</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-bold small text-uppercase">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ (!$editOffer || $editOffer->is_active) ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ ($editOffer && !$editOffer->is_active) ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Short Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $editOffer ? $editOffer->description : '' }}</textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small text-uppercase">Exclusive Perk Details</label>
                        <textarea name="offer_details" class="form-control" rows="4" required placeholder="Describe the PMCC Member only benefit...">{{ $editOffer ? $editOffer->offer_details : '' }}</textarea>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 p-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm rounded-pill text-uppercase">
                        <i class="fas fa-save me-2"></i> {{ $editOffer ? 'Update Offer' : 'Add Sponsor Offer' }}
                    </button>
                    @if($editOffer)
                        <a href="{{ route('admin.sponsors.offers') }}" class="btn btn-light w-100 mt-2 rounded-pill">CANCEL</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- OFFERS LIST -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="card-title fw-bold mb-0">Existing Sponsor Offers</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-3 border-0">Logo</th>
                                <th class="border-0">Sponsor</th>
                                <th class="border-0">Usage</th>
                                <th class="border-0">Badge</th>
                                <th class="border-0">Status</th>
                                <th class="text-end pe-3 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($offers as $o)
                            <tr>
                                <td class="ps-3" style="width: 80px;">
                                    <div class="rounded overflow-hidden bg-white shadow-sm border" style="width: 50px; height: 50px;">
                                        <img src="{{ !empty($o->logo_url) ? (str_starts_with($o->logo_url, 'http') ? $o->logo_url : asset('storage/'.$o->logo_url)) : 'https://placehold.co/100x100?text=Sponsor' }}" class="w-100 h-100" style="object-fit: contain;">
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $o->sponsor_name }}</div>
                                    <div class="small text-muted text-truncate" style="max-width: 200px;">{{ $o->title }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2 rounded-pill fw-bold">
                                        <i class="fas fa-hand-holding-heart me-1"></i> {{ $o->redemptions_count }} Used
                                    </span>
                                </td>
                                <td>
                                    @if($o->is_premium)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 rounded-pill fw-bold">
                                            <i class="fas fa-crown me-1 text-warning"></i> PREMIUM
                                        </span>
                                    @else
                                        <span class="text-muted small">Standard</span>
                                    @endif
                                </td>
                                <td>
                                    @if($o->is_active)
                                        <span class="badge bg-success-subtle text-success px-2 rounded-pill small fw-bold">ACTIVE</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger px-2 rounded-pill small fw-bold">INACTIVE</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.sponsors.offers', ['edit' => $o->id]) }}" class="btn btn-sm btn-outline-info rounded-3 me-1"><i class="fas fa-edit"></i></a>
                                        <button class="btn btn-sm btn-outline-danger rounded-3"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-5 text-muted">No sponsor offers found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                {{ $offers->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
