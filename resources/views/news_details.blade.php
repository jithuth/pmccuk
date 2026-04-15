@extends('layouts.app')

@section('title', $news->title . ' | News')

@section('content')
<article class="py-24 bg-white min-h-screen">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Breadcrumbs -->
        <nav class="mb-12 flex items-center text-xs font-black uppercase tracking-widest text-slate-400">
            <a href="{{ url('/') }}" class="hover:text-primary">Home</a>
            <i class="fas fa-chevron-right mx-3 text-[8px]"></i>
            <a href="{{ url('news') }}" class="hover:text-primary">News Archive</a>
            <i class="fas fa-chevron-right mx-3 text-[8px]"></i>
            <span class="text-slate-900">Current Article</span>
        </nav>

        <header class="mb-16">
            <h1 class="text-5xl md:text-6xl font-black text-slate-900 mb-8 leading-[1.1]">{{ $news->title }}</h1>
            <div class="flex items-center gap-6">
                <div class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center text-white text-lg">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Published On</p>
                    <p class="text-lg font-bold text-slate-900 leading-none">{{ $news->created_at->format('F d, Y') }}</p>
                </div>
            </div>
        </header>

        <!-- Main Banner -->
        <div class="rounded-[3.5rem] overflow-hidden shadow-2xl mb-16 border-8 border-slate-50">
            <img src="{{ $news->image_url ? (str_starts_with($news->image_url, 'http') ? $news->image_url : asset('storage/'.$news->image_url)) : asset('assets/img/pmcc_logo.png') }}" 
                 class="w-full h-auto object-cover aspect-video">
        </div>

        <!-- Content -->
        <div class="prose prose-lg max-w-none text-slate-700 leading-loose prose-h2:font-black prose-h2:text-slate-900 prose-h2:uppercase prose-h2:tracking-tight prose-p:mb-8 font-medium text-justify">
            {!! $news->content !!}
        </div>

        <div class="mt-20 pt-10 border-t flex flex-wrap gap-4 items-center justify-between">
            <div class="flex gap-3">
                <span class="px-5 py-2 bg-slate-100 rounded-full text-xs font-black uppercase tracking-widest text-slate-500">#Community</span>
                <span class="px-5 py-2 bg-slate-100 rounded-full text-xs font-black uppercase tracking-widest text-slate-500">#PMCCNews</span>
            </div>
            <button onclick="window.print()" class="text-sm font-black uppercase tracking-widest text-primary hover:text-secondary"><i class="fas fa-print me-2"></i> Print Article</button>
        </div>
    </div>
</article>

<!-- Related News Section -->
<section class="py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-2xl font-black uppercase tracking-tighter mb-12">More from the Archive</h2>
        <div class="grid md:grid-cols-3 gap-8">
            {{-- Loop handled by controller or simple latest news query --}}
        </div>
    </div>
</section>
@endsection
