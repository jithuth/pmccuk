@extends('layouts.app')

@section('title', 'Community News & Announcements | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Header -->
<section class="py-24 bg-white border-b overflow-hidden relative">
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <div class="text-center">
            <h1 class="text-6xl font-black uppercase tracking-tighter text-slate-900 mb-6">PMCC Network <span class="text-secondary">News</span></h1>
            <div class="h-2 w-32 bg-primary mx-auto mb-8"></div>
            <p class="text-xl text-slate-500 max-w-2xl mx-auto font-medium">Stay updated with the latest community announcements, cultural updates, and local news from Plymouth.</p>
        </div>
    </div>
</section>

<!-- News Grid -->
<section class="py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-12">
            @forelse($news as $n)
            <article class="bg-white rounded-[2.5rem] overflow-hidden shadow-xl shadow-slate-200/50 group hover:-translate-y-2 transition-all duration-300 flex flex-col h-full border border-slate-100">
                <div class="h-64 overflow-hidden relative">
                    <img src="{{ $n->image_url ? (str_starts_with($n->image_url, 'http') ? $n->image_url : asset('storage/'.$n->image_url)) : asset('assets/img/pmcc_logo.png') }}" 
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute top-6 left-6 bg-primary text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-full shadow-lg">
                        {{ $n->created_at->format('M d, Y') }}
                    </div>
                </div>
                <div class="p-10 flex flex-col flex-1">
                    <h3 class="text-2xl font-black text-slate-900 mb-4 leading-tight group-hover:text-primary transition-colors">{{ $n->title }}</h3>
                    <p class="text-slate-500 line-clamp-3 mb-8 text-sm leading-relaxed">{{ strip_tags($n->content) }}</p>
                    
                    <div class="mt-auto pt-6 border-t border-slate-50 flex items-center justify-between">
                        <a href="{{ url('news/'.$n->id) }}" class="inline-flex items-center text-xs font-black uppercase tracking-widest text-secondary hover:text-primary transition-colors group/link">
                            Read Full Article <i class="fas fa-chevron-right ml-2 text-[8px] group-hover/link:translate-x-1 transition-transform"></i>
                        </a>
                        <span class="text-xs font-bold text-slate-300">#PMCCNews</span>
                    </div>
                </div>
            </article>
            @empty
            <div class="col-span-3 text-center py-20">
                <i class="far fa-newspaper fa-4x text-slate-200 mb-6"></i>
                <h2 class="text-2xl font-bold text-slate-400">No news articles published at this time.</h2>
            </div>
            @endforelse
        </div>

        <div class="mt-20">
            {{ $news->links() }}
        </div>
    </div>
</section>
@endsection
