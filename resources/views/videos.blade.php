@extends('layouts.app')

@section('title', 'Video Gallery | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
@php
    if (!function_exists('getYouTubeId')) {
        function getYouTubeId($url) {
            preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?v=|embed\/|v\/|shorts\/))([^\?&\"'>]+)/", $url, $matches);
            return isset($matches[1]) ? $matches[1] : null;
        }
    }
@endphp

<!-- Header -->
<section class="py-20 bg-slate-900 text-white text-center relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-20"></div>
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <h1 class="text-5xl font-black uppercase tracking-tighter mb-4">Video Gallery</h1>
        <div class="h-1 w-20 bg-primary mb-4 mx-auto"></div>
        <p class="text-xl text-slate-400 max-w-2xl mx-auto font-medium">Watch highlights, cultural programs, and event archives from our YouTube stream.</p>
    </div>
</section>

<!-- Videos Grid -->
<section class="py-24 bg-white min-h-[50vh]">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            @forelse($videos as $video)
                @php
                    $youtubeId = getYouTubeId($video->video_url);
                @endphp
                @if($youtubeId)
                    <div class="group bg-white rounded-3xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 border border-slate-100 flex flex-col">
                        <div class="aspect-video relative overflow-hidden bg-black flex-shrink-0">
                            <iframe class="w-full h-full border-0" 
                                    src="https://www.youtube.com/embed/{{ $youtubeId }}" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                    allowfullscreen></iframe>
                            <div class="absolute top-4 left-4 z-10">
                                <span class="bg-white/90 backdrop-blur-md px-3 py-1.5 rounded-full flex items-center shadow-md">
                                    <i class="fab fa-youtube text-red-600 mr-2"></i>
                                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-700">YouTube Video</span>
                                </span>
                            </div>
                        </div>
                        <div class="p-6 flex-grow">
                            <h3 class="text-xl font-bold text-slate-800 mb-2 leading-snug group-hover:text-primary transition-colors">
                                {{ $video->title }}
                            </h3>
                            @if($video->description)
                                <p class="text-slate-500 text-sm leading-relaxed mb-0 font-medium italic">
                                    {!! nl2br(e($video->description)) !!}
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            @empty
                <div class="col-span-full py-20 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                    <div class="text-slate-300 text-5xl mb-4"><i class="fab fa-youtube text-6xl text-slate-200"></i></div>
                    <h3 class="text-slate-500 font-bold uppercase tracking-wider">No Videos Found</h3>
                    <p class="text-slate-400 mt-2">We are currently curating and uploading our video library.</p>
                </div>
            @endforelse
        </div>

        @if($videos->hasPages())
            <div class="mt-16 flex justify-center">
                {{ $videos->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
