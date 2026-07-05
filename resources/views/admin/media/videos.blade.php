@extends('layouts.admin')

@section('page_title', 'YouTube Video Gallery')

@section('content')
@php
    if (!function_exists('getYouTubeId')) {
        function getYouTubeId($url) {
            preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?v=|embed\/|v\/|shorts\/))([^\?&\"'>]+)/", $url, $matches);
            return isset($matches[1]) ? $matches[1] : null;
        }
    }
@endphp

<div class="row g-4">
    <!-- Video Creation Panel -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="card-title fw-bold mb-0 text-slate-800">Add YouTube Video</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.videos.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label text-slate-500 font-bold uppercase text-[10px] tracking-wider">Video Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Cultural Program 2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-500 font-bold uppercase text-[10px] tracking-wider">Short Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief details about the performance..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-500 font-bold uppercase text-[10px] tracking-wider">YouTube URL</label>
                        <input type="url" name="video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=..." required>
                        <small class="text-muted text-[10px]">Shorts links are also supported.</small>
                    </div>
                    <button type="submit" class="btn btn-danger w-100 fw-bold rounded-pill shadow-sm">
                        <i class="fab fa-youtube me-1"></i> SAVE TO GALLERY
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Videos List Grid -->
    <div class="col-md-8">
        <div class="row g-3">
            @forelse($videos as $video)
                @php
                    $youtubeId = getYouTubeId($video->video_url);
                @endphp
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
                        <div class="bg-dark d-flex align-items-center justify-content-center position-relative" style="height: 180px;">
                            @if($youtubeId)
                                <iframe class="w-full h-full border-0" 
                                        src="https://www.youtube.com/embed/{{ $youtubeId }}" 
                                        allowfullscreen 
                                        style="height: 180px; width: 100%;"></iframe>
                            @else
                                <div class="text-center text-white p-4">
                                    <i class="fab fa-youtube text-danger fa-3x mb-2"></i>
                                    <p class="mb-0 small font-italic">Invalid YouTube Link</p>
                                    <small class="text-xs text-muted">{{ $video->video_url }}</small>
                                </div>
                            @endif
                            <div class="position-absolute top-0 end-0 m-2 z-3">
                                <form action="{{ route('admin.videos.delete', $video->id) }}" method="POST" onsubmit="return confirm('Delete this video from the public gallery?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger rounded-circle shadow-sm" style="z-index: 99;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body bg-white p-3">
                            <h6 class="fw-bold text-slate-800 mb-1">{{ $video->title }}</h6>
                            @if($video->description)
                                <p class="text-xs text-muted mb-0 line-clamp-2 italic">{{ $video->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center py-5 bg-white rounded-3 shadow-sm">
                        <i class="fab fa-youtube fa-3x text-light mb-3"></i>
                        <p class="text-muted">No YouTube videos added yet.</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if($videos->hasPages())
            <div class="mt-4">
                {{ $videos->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
