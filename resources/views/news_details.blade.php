@extends('layouts.app')

@php
    $item = $news ?? $article ?? null;
@endphp

@section('title', ($item ? $item->title : 'News Article') . ' | News')

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

        @if($item)
        <header class="mb-16">
            <h1 class="text-5xl md:text-6xl font-black text-slate-900 mb-8 leading-[1.1]">{{ $item->title }}</h1>
            <div class="flex items-center gap-6">
                <div class="w-12 h-12 bg-primary rounded-2xl flex items-center justify-center text-white text-lg">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Published On</p>
                    <p class="text-lg font-bold text-slate-900 leading-none">{{ $item->created_at ? $item->created_at->format('F d, Y') : 'Recent' }}</p>
                </div>
            </div>
        </header>

        <!-- Main Banner -->
        <div class="rounded-[3.5rem] overflow-hidden shadow-2xl mb-16 border-8 border-slate-50">
            <img src="{{ !empty($item->image_url) ? (str_starts_with($item->image_url, 'http') ? $item->image_url : asset('storage/'.$item->image_url)) : asset('assets/img/pmcc_logo.png') }}" 
                 class="w-full h-auto object-cover aspect-video" alt="{{ $item->title }}">
        </div>

        <!-- Content -->
        <div class="prose prose-lg max-w-none text-slate-700 leading-loose prose-h2:font-black prose-h2:text-slate-900 prose-h2:uppercase prose-h2:tracking-tight prose-p:mb-8 font-medium text-justify">
            {!! $item->content !!}
        </div>

        <div class="mt-20 pt-10 border-t flex flex-wrap gap-4 items-center justify-between">
            <div class="flex gap-3">
                <span class="px-5 py-2 bg-slate-100 rounded-full text-xs font-black uppercase tracking-widest text-slate-500">#Community</span>
                <span class="px-5 py-2 bg-slate-100 rounded-full text-xs font-black uppercase tracking-widest text-slate-500">#PMCCNews</span>
            </div>
            <button onclick="window.print()" class="text-sm font-black uppercase tracking-widest text-primary hover:text-secondary"><i class="fas fa-print me-2"></i> Print Article</button>
        </div>
        @else
        <div class="py-20 text-center">
            <h2 class="text-2xl font-bold text-slate-400">Article not found.</h2>
            <a href="{{ url('news') }}" class="btn btn-primary mt-4">Return to News Archive</a>
        </div>
        @endif
    </div>
</article>

<!-- Related News Section -->
<section class="py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between mb-12">
            <h2 class="text-2xl font-black uppercase tracking-tighter text-slate-900">More from the Archive</h2>
            <a href="{{ url('news') }}" class="text-xs font-black uppercase tracking-widest text-primary hover:text-secondary">View All <i class="fas fa-arrow-right ms-1"></i></a>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            @if(isset($relatedNews) && $relatedNews->count() > 0)
                @foreach($relatedNews as $rn)
                <article class="bg-white rounded-3xl p-6 shadow-sm hover:shadow-md transition flex flex-col justify-between border border-slate-100">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-primary bg-primary/10 px-3 py-1 rounded-full mb-3 inline-block">
                            {{ $rn->created_at ? $rn->created_at->format('M d, Y') : '' }}
                        </span>
                        <h3 class="text-lg font-bold text-slate-900 mb-2 leading-snug">
                            <a href="{{ url('news/' . $rn->id) }}" class="hover:text-primary transition-colors">{{ $rn->title }}</a>
                        </h3>
                    </div>
                    <a href="{{ url('news/' . $rn->id) }}" class="inline-flex items-center text-xs font-black uppercase tracking-widest text-secondary hover:text-primary mt-4">
                        Read Story <i class="fas fa-chevron-right ml-1.5 text-[8px]"></i>
                    </a>
                </article>
                @endforeach
            @else
                <p class="text-sm text-slate-400 col-span-3">No other articles in the archive.</p>
            @endif
        </div>
    </div>
</section>
@endsection
