@extends('layouts.app')

@section('title', 'Community Gallery | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Header -->
<section class="py-20 bg-slate-900 text-white text-center relative overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-20"></div>
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <h1 class="text-5xl font-black uppercase tracking-tighter mb-4">Our Memories</h1>
        <p class="text-xl text-slate-400 max-w-2xl mx-auto font-medium">Capturing the vibrant cultural life and celebrations of PMCC-UK.</p>
    </div>
</section>

<!-- Gallery Grid -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4">
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

        <div class="mt-20 flex justify-center">
            {{ $images->links() }}
        </div>
    </div>
</section>
@endsection
