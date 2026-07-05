@extends('layouts.admin')

@section('page_title', 'Photo Albums')

@section('content')
<div class="row g-4">
    <!-- Album Creation Panel -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="card-title fw-bold mb-0 text-slate-800">Create Photo Album</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.albums.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label text-slate-500 font-bold uppercase text-[10px] tracking-wider">Album Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Vishu Festival 2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-500 font-bold uppercase text-[10px] tracking-wider">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe the album context..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-500 font-bold uppercase text-[10px] tracking-wider">Cover Image (Optional)</label>
                        <input type="file" name="cover_image" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm">
                        <i class="fas fa-plus me-1"></i> CREATE ALBUM
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Albums List Grid -->
    <div class="col-md-8">
        <div class="row g-3">
            @forelse($albums as $album)
                @php
                    $coverImage = $album->cover_image_url ? asset('storage/' . $album->cover_image_url) : 'https://images.unsplash.com/photo-1542038784456-1ea8e935640e?q=80&w=300&auto=format&fit=crop';
                @endphp
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
                        <div class="position-relative" style="height: 160px; bg-slate-100">
                            <img src="{{ $coverImage }}" class="w-full h-full object-cover" style="height: 160px; width: 100%; object-fit: cover;">
                            <span class="position-absolute top-0 start-0 m-3 badge bg-dark text-white font-bold px-2.5 py-1.5 rounded shadow">
                                {{ $album->photos_count }} {{ Str::plural('Photo', $album->photos_count) }}
                            </span>
                            <div class="position-absolute top-0 end-0 m-3">
                                <form action="{{ route('admin.albums.delete', $album->id) }}" method="POST" onsubmit="return confirm('Deleting this album will delete all its photo assets. Proceed?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-circle shadow-sm">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body bg-white p-3">
                            <h6 class="fw-bold text-slate-800 mb-1">{{ $album->name }}</h6>
                            <p class="text-xs text-muted mb-0 line-clamp-2 italic">{{ $album->description ?? 'No description.' }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5 bg-white rounded-3 shadow-sm">
                        <i class="fas fa-images fa-3x text-light mb-3"></i>
                        <p class="text-muted">No albums created yet.</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if($albums->hasPages())
            <div class="mt-4">
                {{ $albums->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
