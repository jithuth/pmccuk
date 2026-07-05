@extends('layouts.app')

@section('title', ($album->name ?? 'Gallery') . ' | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Header -->
<section class="py-20 bg-slate-900 text-white text-center relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-20"></div>
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <h1 class="text-5xl font-black uppercase tracking-tighter mb-4">{{ $album->name ?? 'Photo Gallery' }}</h1>
        <div class="h-1 w-20 bg-primary mb-4 mx-auto"></div>
        <p class="text-xl text-slate-400 max-w-2xl mx-auto font-medium">{{ $album->description ?? 'Capturing the vibrant moments and celebrations of PMCC-UK.' }}</p>
        
        <div class="mt-8">
            <a href="{{ route('gallery') }}" class="inline-flex items-center bg-white/10 hover:bg-white/20 text-white font-bold text-xs uppercase tracking-widest px-6 py-3 rounded-full border border-white/10 transition-colors">
                <i class="fas fa-chevron-left mr-2"></i> Back to Albums
            </a>
        </div>
    </div>
</section>

<!-- Gallery Grid -->
<section class="py-24 bg-white min-h-[50vh]">
    <div class="max-w-7xl mx-auto px-4">
        @if(count($images) > 0)
            <div class="columns-1 md:columns-2 lg:columns-4 gap-6 space-y-6">
                @foreach($images as $img)
                <div class="relative group overflow-hidden rounded-2xl shadow-lg break-inside-avoid">
                    <img src="{{ asset('storage/'.$img->image_url) }}" 
                         class="w-full h-auto object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex items-end p-8">
                        <div>
                            <p class="text-white font-bold text-sm">{{ $img->title ?? 'PMCC Event' }}</p>
                            <p class="text-primary text-[10px] font-black uppercase tracking-widest mt-1">Photo Archive</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @if($images->hasPages())
                <div class="mt-20 flex justify-center">
                    {{ $images->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-20 bg-slate-50 rounded-2xl border border-dashed border-slate-300 max-w-3xl mx-auto">
                <div class="text-slate-300 text-5xl mb-4"><i class="fas fa-images"></i></div>
                <h3 class="text-slate-500 font-bold uppercase tracking-wider">No Photos Found</h3>
                <p class="text-slate-400 mt-2">There are currently no photos inside this album directory.</p>
            </div>
        @endif
    </div>
</section>
@endsection
