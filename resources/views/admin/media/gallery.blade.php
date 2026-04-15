@extends('layouts.admin')

@section('page_title', 'Media Gallery')

@section('content')
<div class="card shadow-sm border-0 rounded-3 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="card-title fw-bold mb-0">Upload New Media</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.gallery.add') }}" method="POST" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-md-5">
                <input type="text" name="title" class="form-control" placeholder="Image Title (Optional)">
            </div>
            <div class="col-md-5">
                <input type="file" name="gallery_image" class="form-control" required>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill">
                    <i class="fas fa-upload me-1"></i> UPLOAD
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    @forelse($gallery as $item)
    <div class="col-md-2 col-sm-4 col-6">
        <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden position-relative group-hover">
            <img src="{{ asset('storage/' . $item->image_url) }}" class="card-img-top" style="height: 150px; object-fit: cover;">
            <div class="position-absolute top-0 end-0 p-2 opacity-0-hover">
                <form action="{{ route('admin.gallery.delete', $item->id) }}" method="POST" onsubmit="return confirm('Delete image?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger rounded-circle shadow-sm">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
            </div>
            @if($item->title)
            <div class="card-footer bg-white border-0 py-2">
                <small class="text-truncate d-block fw-bold text-muted">{{ $item->title }}</small>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="text-center py-5 bg-white rounded-3 shadow-sm">
            <i class="fas fa-images fa-3x text-light mb-3"></i>
            <p class="text-muted">No media found in gallery. Start uploading!</p>
        </div>
    </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $gallery->links() }}
</div>

<style>
    .group-hover:hover .opacity-0-hover { opacity: 1 !important; transition: 0.3s; }
    .opacity-0-hover { opacity: 0; transition: 0.3s; }
</style>
@endsection
