@extends('layouts.app')

@section('title', 'Photo Albums | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Header -->
<section class="py-20 bg-slate-900 text-white text-center relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-20"></div>
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <h1 class="text-5xl font-black uppercase tracking-tighter mb-4">Our Photo Albums</h1>
        <div class="h-1 w-20 bg-primary mb-4 mx-auto"></div>
        <p class="text-xl text-slate-400 max-w-2xl mx-auto font-medium">Browse through our curated albums capturing local cultural events and celebrations.</p>
    </div>
</section>

<!-- Albums Grid -->
<section class="py-24 bg-white min-h-[50vh]">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($albums as $album)
                @php
                    $coverImage = $album->cover_image_url ? asset('storage/' . $album->cover_image_url) : 'https://images.unsplash.com/photo-1542038784456-1ea8e935640e?q=80&w=600&auto=format&fit=crop';
                @endphp
                <div class="group bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 border border-slate-100 flex flex-col h-full">
                    <div class="aspect-[4/3] relative overflow-hidden bg-slate-100">
                        <img src="{{ $coverImage }}" 
                             alt="{{ $album->name }}" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-end p-6">
                            <span class="bg-primary text-white text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-full shadow-md">
                                {{ $album->photos_count }} {{ Str::plural('Photo', $album->photos_count) }}
                            </span>
                        </div>
                    </div>
                    <div class="p-6 flex-grow flex flex-col justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2 leading-snug group-hover:text-primary transition-colors">
                                {{ $album->name }}
                            </h3>
                            <p class="text-slate-500 text-sm leading-relaxed mb-4 line-clamp-3">
                                {{ $album->description ?? 'No description available for this album.' }}
                            </p>
                        </div>
                        <a href="{{ route('gallery', ['album_id' => $album->id]) }}" 
                           class="inline-flex items-center text-primary font-bold text-sm tracking-wider uppercase group-hover:underline">
                            View Album <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                    <div class="text-slate-300 text-5xl mb-4"><i class="fas fa-images"></i></div>
                    <h3 class="text-slate-500 font-bold uppercase tracking-wider">No Albums Found</h3>
                    <p class="text-slate-400 mt-2">We are currently preparing our photography showcase.</p>
                </div>
            @endforelse
        </div>

        @if($albums->hasPages())
            <div class="mt-16 flex justify-center">
                {{ $albums->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
