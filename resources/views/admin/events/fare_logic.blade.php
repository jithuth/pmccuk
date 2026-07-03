@extends('layouts.admin')
@section('page_title', 'Fare Pricing Logic')
@section('content')
<div class="row">
    <!-- Categories Panel -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold small text-uppercase tracking-wider"><i class="fas fa-users me-2"></i> Attendee Categories</h5>
                <button class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#categoryModal">
                    <i class="fas fa-plus me-1"></i> ADD
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-3">Name</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                            <tr>
                                <td class="ps-3 fw-bold text-dark">{{ $cat->name }}</td>
                                <td class="text-end pe-3">
                                    <div class="btn-group">
                                        <button class="btn btn-link text-info p-1" onclick="editCategory({{ $cat->id }}, '{{ $cat->name }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('admin.events.fare-logic.categories.delete', $cat->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove category?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-1"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center py-4 text-muted">No categories defined</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Rubrics Panel -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold small text-uppercase tracking-wider"><i class="fas fa-file-invoice-dollar me-2"></i> Pricing Rubrics</h5>
                <button class="btn btn-sm btn-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#rubricModal" onclick="resetRubricForm()">
                    <i class="fas fa-plus me-1"></i> NEW RUBRIC
                </button>
            </div>
            <div class="card-body p-4 bg-light">
                <div class="row g-4">
                    @forelse($rubrics as $rubric)
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                                    <div>
                                        <h6 class="fw-bold text-primary mb-1">{{ $rubric->name }}</h6>
                                        <span class="badge bg-info-subtle text-info rounded-pill small">ID: #{{ $rubric->id }}</span>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-link text-secondary p-0" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="editRubric({{ json_encode($rubric) }})"><i class="fas fa-edit me-2"></i> Edit Rubric</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('admin.events.fare-logic.rubrics.delete', $rubric->id) }}" method="POST" onsubmit="return confirm('Delete this rubric?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash me-2"></i> Delete</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="pricing-items">
                                    @php $itemsCount = 0; @endphp
                                    @foreach($rubric->items as $item)
                                    <div class="d-flex justify-content-between align-items-center mb-1 small border-bottom border-light pb-1">
                                        <span class="text-secondary fw-bold text-truncate" style="max-width: 100px;">{{ $item->category->name }}</span>
                                        <div class="text-end">
                                            <span class="text-dark">M: £{{ number_format($item->member_price, 2) }}</span>
                                            <span class="text-muted ms-2">G: £{{ number_format($item->guest_price, 2) }}</span>
                                        </div>
                                    </div>
                                    @php $itemsCount++; @endphp
                                    @endforeach
                                    @if($itemsCount == 0)
                                        <div class="text-center py-2 text-muted small italic">No rates defined</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <h5>No pricing rubrics found.</h5>
                        <p class="text-muted">Create your first rubric to start automating event pricing.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('admin.events.fare-logic.categories.save') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="cat_id">
                <div class="modal-header bg-dark text-white border-0">
                    <h6 class="modal-title fw-bold" id="catModalTitle">Add Attendee Category</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Category Name</label>
                        <input type="text" name="name" id="cat_name" class="form-control rounded-3" placeholder="e.g. Adult, Child (5-12)" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">SAVE CATEGORY</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rubric Modal -->
<div class="modal fade" id="rubricModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <form action="{{ route('admin.events.fare-logic.rubrics.save') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="rubric_id">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="rubricModalTitle">Create Pricing Rubric</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm rounded-3 mb-4">
                        <div class="card-body">
                            <label class="form-label fw-bold text-dark">Rubric Name</label>
                            <input type="text" name="name" id="rubric_name" class="form-control form-control-lg border-2" placeholder="e.g. Standard Convention Rates" required>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 text-secondary text-uppercase small tracking-wider"><i class="fas fa-list-ul me-2"></i> Category Rates</h6>
                    <div class="row g-3">
                        @foreach($categories as $cat)
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-3">
                                <div class="card-body p-3">
                                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">{{ $cat->name }}</h6>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="small text-muted d-block">Member Price</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light">£</span>
                                                <input type="number" step="0.01" name="items[{{ $cat->id }}][member]" id="rate_m_{{ $cat->id }}" class="form-control" value="0.00">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="small text-muted d-block">Guest Price</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light">£</span>
                                                <input type="number" step="0.01" name="items[{{ $cat->id }}][guest]" id="rate_g_{{ $cat->id }}" class="form-control" value="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0 p-3">
                    <button type="button" class="btn btn-link text-secondary fw-bold text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-5 rounded-pill fw-bold shadow">SAVE PRICING RULES</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function editCategory(id, name) {
        document.getElementById('cat_id').value = id;
        document.getElementById('cat_name').value = name;
        document.getElementById('catModalTitle').innerText = 'Edit Category';
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
    }

    function resetRubricForm() {
        document.getElementById('rubric_id').value = '';
        document.getElementById('rubric_name').value = '';
        document.getElementById('rubricModalTitle').innerText = 'Create Pricing Rubric';
        @foreach($categories as $cat)
            document.getElementById('rate_m_{{ $cat->id }}').value = '0.00';
            document.getElementById('rate_g_{{ $cat->id }}').value = '0.00';
        @endforeach
    }

    function editRubric(rubric) {
        resetRubricForm();
        document.getElementById('rubric_id').value = rubric.id;
        document.getElementById('rubric_name').value = rubric.name;
        document.getElementById('rubricModalTitle').innerText = 'Edit Pricing Rubric';
        
        if(rubric.items) {
            rubric.items.forEach(item => {
                let mInput = document.getElementById('rate_m_' + item.category_id);
                let gInput = document.getElementById('rate_g_' + item.category_id);
                if(mInput) mInput.value = item.member_price;
                if(gInput) gInput.value = item.guest_price;
            });
        }
        
        new bootstrap.Modal(document.getElementById('rubricModal')).show();
    }
</script>
<style>
    .tracking-wider { letter-spacing: 0.05em; }
    .bg-info-subtle { background-color: #e1f5fe; }
</style>
@endsection
