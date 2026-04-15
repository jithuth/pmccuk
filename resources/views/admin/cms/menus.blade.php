@extends('layouts.admin')

@section('page_title', 'Navigation Menu Editor')

@section('content')
<div class="row">
    <!-- CREATE MENU ITEM -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="card-title fw-bold mb-0"><i class="fas fa-plus-circle me-1"></i> Add New Link</h5>
            </div>
            <form action="{{ route('admin.menus.add') }}" method="POST">
                @csrf
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Menu Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. About Us" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Navigation URL</label>
                        <input type="text" name="url" class="form-control" placeholder="e.g. /about or https://..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Parent Menu</label>
                        <select name="parent_id" class="form-select">
                            <option value="0">--- Top Level ---</option>
                            @foreach($allMenus->where('parent_id', 0) as $pm)
                                <option value="{{ $pm->id }}">{{ $pm->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Order No.</label>
                            <input type="number" name="order_no" class="form-control" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-uppercase">Visibility</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Visible</option>
                                <option value="0">Hidden</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light border-0 p-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm rounded-pill">
                        <i class="fas fa-save me-2"></i> SAVE MENU ITEM
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MENUS LIST -->
    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3">
                <h5 class="card-title fw-bold mb-0 text-primary">Active Navigation Structure</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase">
                            <tr>
                                <th class="ps-4 border-0">Order</th>
                                <th class="border-0">Menu Item</th>
                                <th class="border-0">Path / URL</th>
                                <th class="border-0">Level</th>
                                <th class="border-0">Status</th>
                                <th class="text-end pe-4 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allMenus->sortBy('order_no') as $m)
                            <tr class="{{ $m->parent_id == 0 ? 'bg-light-subtle fw-bold' : '' }}">
                                <td class="ps-4" style="width: 80px;">
                                    <span class="badge bg-secondary font-monospace">{{ $m->order_no }}</span>
                                </td>
                                <td>
                                    @if($m->parent_id != 0) <span class="text-muted ms-3">↳</span> @endif
                                    {{ $m->title }}
                                </td>
                                <td><code class="text-xs">{{ $m->url }}</code></td>
                                <td>
                                    @if($m->parent_id == 0)
                                        <span class="badge bg-primary-subtle text-primary small">PRIMARY</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info small">SUB-MENU</span>
                                    @endif
                                </td>
                                <td>
                                    @if($m->is_active)
                                        <span class="badge bg-success rounded-pill px-3 py-1">ACTIVE</span>
                                    @else
                                        <span class="badge bg-danger rounded-pill px-3 py-1">HIDDEN</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info border-0 rounded-3 me-2" data-bs-toggle="modal" data-bs-target="#editMenu{{ $m->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('admin.menus.delete', $m->id) }}" method="POST" onsubmit="return confirm('Delete this menu item?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-3"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>

                                    <!-- EDIT MODAL -->
                                    <div class="modal fade" id="editMenu{{ $m->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow-lg text-start">
                                                <div class="modal-header bg-info text-white border-0">
                                                    <h5 class="modal-title font-bold">Edit Menu Link</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('admin.menus.update', $m->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body p-4">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small text-uppercase">Menu Title</label>
                                                            <input type="text" name="title" class="form-control" value="{{ $m->title }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small text-uppercase">Navigation URL</label>
                                                            <input type="text" name="url" class="form-control" value="{{ $m->url }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold small text-uppercase">Parent Menu</label>
                                                            <select name="parent_id" class="form-select">
                                                                <option value="0">--- Top Level ---</option>
                                                                @foreach($allMenus->where('parent_id', 0) as $pm)
                                                                    <option value="{{ $pm->id }}" {{ $m->parent_id == $pm->id ? 'selected' : '' }}>{{ $pm->title }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="row g-3">
                                                            <div class="col-6">
                                                                <label class="form-label fw-bold small text-uppercase">Order</label>
                                                                <input type="number" name="order_no" class="form-control" value="{{ $m->order_no }}">
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="form-label fw-bold small text-uppercase">Visibility</label>
                                                                <select name="is_active" class="form-select">
                                                                    <option value="1" {{ $m->is_active ? 'selected' : '' }}>Visible</option>
                                                                    <option value="0" {{ !$m->is_active ? 'selected' : '' }}>Hidden</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light border-0">
                                                        <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-info text-white px-4 fw-bold rounded-pill shadow-sm">SAVE CHANGES</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No menu items found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
