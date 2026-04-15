@extends('layouts.app')

@section('title', 'Community Leadership | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Header -->
<section class="py-20 bg-primary text-white text-center relative overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            <path d="M0,0 L100,0 L100,100 L0,100 Z" fill="url(#grid)"></path>
            <defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"></path></pattern></defs>
        </svg>
    </div>
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <h1 class="text-5xl font-black uppercase tracking-tighter mb-4">
            {{ isset($category) && $category === 'former' ? 'Former Leaders' : 'Our Leadership' }}
        </h1>
        <p class="text-xl text-primary-subtle max-w-2xl mx-auto font-medium opacity-80 mb-10">Meet the dedicated volunteers and community leaders of PMCC-UK.</p>
        <div class="flex justify-center gap-4">
            <a href="{{ route('team') }}?cat=current" class="px-8 py-3 rounded-full font-black uppercase tracking-widest text-sm {{ (!isset($category) || $category === 'current') ? 'bg-white text-primary' : 'bg-white/20 text-white/80 hover:bg-white/30' }} transition-all shadow-lg">
                Current Leaders
            </a>
            <a href="{{ route('team') }}?cat=former" class="px-8 py-3 rounded-full font-black uppercase tracking-widest text-sm {{ (isset($category) && $category === 'former') ? 'bg-white text-primary' : 'bg-white/20 text-white/80 hover:bg-white/30' }} transition-all shadow-lg">
                <i class="fas fa-history mr-2"></i> Former Leaders
            </a>
        </div>
    </div>
</section>

<!-- Team Grid -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12">
            @foreach($members as $m)
            <div class="group text-center">
                <div class="relative mb-8 inline-block">
                    <div class="absolute inset-0 bg-secondary rounded-[3rem] rotate-6 group-hover:rotate-0 transition-transform duration-500"></div>
                    <div class="relative w-64 h-64 rounded-[3rem] overflow-hidden border-4 border-white shadow-2xl">
                        <img src="{{ $m->image_url ? (str_starts_with($m->image_url, 'http') ? $m->image_url : asset('storage/'.$m->image_url)) : 'https://placehold.co/400x400?text='.$m->name }}" 
                             class="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-700 scale-110 group-hover:scale-100">
                    </div>
                    <div class="absolute -bottom-4 right-0 bg-white p-3 rounded-2xl shadow-xl flex gap-3 translate-x-4">
                        <a href="#" class="text-slate-400 hover:text-primary transition-colors"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-slate-400 hover:text-primary transition-colors"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <h3 class="text-2xl font-black text-slate-900 mb-1">{{ $m->name }}</h3>
                <p class="text-xs font-black uppercase tracking-widest text-secondary">{{ $m->role }}</p>
                <p class="text-sm text-slate-400 mt-4 px-6 line-clamp-2 italic">{{ $m->bio ?? 'Dedicated core committee member serving since ' . ($m->service_years ?? '2020') }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-16 bg-slate-50 border-t">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <h2 class="text-2xl font-bold text-slate-900 mb-6">Want to contribute to our community?</h2>
        <a href="{{ url('contact') }}" class="inline-block bg-primary text-white px-10 py-4 rounded-full font-black uppercase tracking-widest hover:bg-zinc-800 transition shadow-xl">Join the Committee</a>
    </div>
</section>
@endsection
