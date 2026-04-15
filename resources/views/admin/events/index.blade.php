@extends('layouts.admin')

@section('page_title', 'Event Management')

@section('content')
<div class="row">
    <!-- EVENT FORM -->
    <div class="col-md-5">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header {{ $editEvent ? 'bg-info' : 'bg-primary' }} text-white border-0 py-3">
                <h5 class="card-title fw-bold mb-0">
                    <i class="fas {{ $editEvent ? 'fa-edit' : 'fa-plus-circle' }} me-1"></i> 
                    {{ $editEvent ? 'Edit Event' : 'Add New Event' }}
                </h5>
            </div>
            <form action="{{ $editEvent ? route('admin.events.update', $editEvent->id) : route('admin.events.add') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Event Title</label>
                        <input type="text" name="title" class="form-control" required value="{{ $editEvent ? $editEvent->title : '' }}">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Event Date</label>
                            <input type="date" name="event_date" class="form-control" required value="{{ $editEvent ? $editEvent->event_date : '' }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="City / Hall" value="{{ $editEvent ? $editEvent->location : '' }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Associate Fare Rubric</label>
                        <select name="rubric_id" id="rubric_selector" class="form-select" onchange="applyRubric()">
                            <option value="">-- Apply a Rubric --</option>
                            @foreach($rubrics as $rubric)
                                <option value="{{ $rubric->id }}" {{ ($editEvent && $editEvent->rubric_id == $rubric->id) ? 'selected' : '' }} data-items="{{ json_encode($rubric->items) }}">
                                    {{ $rubric->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="border rounded-3 p-3 bg-light mb-3">
                        <h6 class="fw-bold mb-3 border-bottom pb-1 small text-uppercase">Price Categories</h6>
                        <div id="priceCategoriesContainer">
                            @foreach($categories as $cat)
                                @php
                                    $price = $editEvent ? $editEvent->prices->where('category_id', $cat->id)->first() : null;
                                    $mp = $price ? $price->member_price : '0.00';
                                    $gp = $price ? $price->guest_price : '0.00';
                                    $visible = ($price || !$editEvent) ? '' : 'd-none';
                                @endphp
                                <div class="row category-row g-2 mb-2 {{ $visible }}" data-cat-id="{{ $cat->id }}">
                                    <div class="col-3 pt-1"><small class="fw-bold">{{ $cat->name }}</small></div>
                                    <div class="col-3"><input type="number" step="0.01" name="prices[{{ $cat->id }}][member]" class="form-control form-control-sm price-member" placeholder="Member" value="{{ $mp }}"></div>
                                    <div class="col-3"><input type="number" step="0.01" name="prices[{{ $cat->id }}][guest]" class="form-control form-control-sm price-guest" placeholder="Guest" value="{{ $gp }}"></div>
                                    <div class="col-3 pt-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="prices[{{ $cat->id }}][guest_visible]" id="gv_{{ $cat->id }}" {{ (!$price || $price->is_guest_visible) ? 'checked' : '' }}>
                                            <label class="form-check-label text-[10px]" for="gv_{{ $cat->id }}">Guest View?</label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="allow_guest_packages" id="pkgSwitch" {{ (!$editEvent || $editEvent->allow_guest_packages) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold small text-uppercase" for="pkgSwitch">Allow Guest Packages?</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $editEvent ? $editEvent->description : '' }}</textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold small text-uppercase">Banner Image</label>
                        <input type="file" name="event_image" class="form-control mb-1">
                        <input type="text" name="image_url" class="form-control form-control-sm" placeholder="Or URL" value="{{ $editEvent ? $editEvent->image_url : '' }}">
                    </div>
                </div>
                <div class="card-footer bg-white border-0 p-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm rounded-pill">
                        <i class="fas fa-save me-2"></i> {{ $editEvent ? 'UPDATE EVENT' : 'CREATE EVENT' }}
                    </button>
                    @if($editEvent)
                        <a href="{{ route('admin.events.index') }}" class="btn btn-light w-100 mt-2 rounded-pill">CANCEL</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- EVENTS LIST -->
    <div class="col-md-7">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="card-title fw-bold mb-0">Existing Events</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-3 border-0">Date</th>
                                <th class="border-0">Event</th>
                                <th class="border-0">Pricing</th>
                                <th class="border-0">Stats</th>
                                <th class="text-end pe-3 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($events as $row)
                            <tr>
                                <td class="ps-3"><span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">{{ \Carbon\Carbon::parse($row->event_date)->format('M d') }}</span></td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $row->title }}</div>
                                    <small class="text-muted">{{ $row->location }}</small>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap" style="max-width: 200px;">
                                        @foreach($row->prices as $p)
                                            <div class="badge bg-light text-dark border m-1 p-2 text-start" style="font-size: 9px; min-width: 90px;">
                                                <div class="border-bottom mb-1 pb-1 opacity-50">{{ $p->category->name ?? 'Cat' }}</div>
                                                <div class="text-success">M: £{{ number_format($p->member_price, 2) }}</div>
                                                <div class="text-primary">G: £{{ number_format($p->guest_price, 2) }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="small">
                                    <div class="text-success small fw-bold">Apprv: {{ $row->approved_count }}</div>
                                    <div class="text-info small fw-bold">Attnd: {{ $row->attendance_count }}</div>
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a href="{{ route('admin.events.index', ['edit' => $row->id]) }}" class="btn btn-sm btn-outline-info rounded-3" title="Edit Event"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('admin.events.delete', $row->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this event and all associated prices?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" title="Delete Event"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">No events found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0">
                {{ $events->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function applyRubric() {
        const selector = document.getElementById('rubric_selector');
        const option = selector.options[selector.selectedIndex];
        if (!option.value) return;

        const items = JSON.parse(option.getAttribute('data-items'));
        const container = document.getElementById('priceCategoriesContainer');
        const rows = container.querySelectorAll('.category-row');

        rows.forEach(row => { row.classList.add('d-none'); });

        items.forEach(item => {
            const row = container.querySelector(`.category-row[data-cat-id="${item.category_id}"]`);
            if (row) {
                row.classList.remove('d-none');
                row.querySelector('.price-member').value = item.member_price;
                row.querySelector('.price-guest').value = item.guest_price;
            }
        });
    }
</script>
@endsection
