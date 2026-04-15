@extends('layouts.app')

@section('title', 'Events | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Premium Header -->
<section class="relative bg-slate-900 py-32 overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-full opacity-10 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
    <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
        <h1 class="text-6xl font-black text-white mb-6 tracking-tighter uppercase italic">Community <span class="text-primary italic">Events</span></h1>
        <div class="h-1.5 w-32 bg-primary mx-auto mb-8"></div>
        <p class="text-slate-400 max-w-2xl mx-auto text-lg font-medium leading-relaxed uppercase tracking-widest">Mark your calendars for our upcoming cultural celebrations and community gatherings.</p>
    </div>
</section>

<section class="py-24 bg-[#f8fafc]">
    <div class="max-w-7xl mx-auto px-4">
        <div class="space-y-12">
            @forelse($events as $event)
                @php
                    $e_date = strtotime($event->event_date);
                    $is_past = $e_date < time();
                @endphp
                <div class="group flex flex-col lg:flex-row bg-white rounded-[2rem] overflow-hidden shadow-2xl shadow-slate-200/50 border border-slate-100 hover:shadow-primary/10 transition-all duration-500 {{ $is_past ? 'opacity-80' : '' }}">
                    
                    <!-- Unique Date Box -->
                    <div class="lg:w-56 {{ $is_past ? 'bg-slate-50' : 'bg-primary/5' }} flex flex-col items-center justify-center p-10 border-r border-slate-50">
                        <span class="text-xs font-black {{ $is_past ? 'text-slate-400' : 'text-primary' }} uppercase tracking-[0.3em] mb-4">{{ date('F', $e_date) }}</span>
                        <span class="text-7xl font-black {{ $is_past ? 'text-slate-300' : 'text-slate-900' }} leading-none tracking-tighter">{{ date('d', $e_date) }}</span>
                        <span class="text-sm font-black text-secondary mt-4 tracking-[0.2em]">{{ date('Y', $e_date) }}</span>
                    </div>

                    <!-- Image Section -->
                    <div class="lg:w-96 relative h-64 lg:h-auto overflow-hidden bg-slate-900">
                        <img src="{{ $event->image_url }}" 
                            class="w-full h-full object-cover group-hover:scale-110 transition duration-700 {{ $is_past ? 'grayscale' : '' }}">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 to-transparent"></div>
                    </div>

                    <!-- Content Details -->
                    <div class="flex-1 p-10 lg:p-14 flex flex-col justify-center">
                        <div class="flex items-center space-x-3 text-[10px] font-black uppercase tracking-[0.2em] {{ $is_past ? 'text-slate-400' : 'text-primary' }} mb-6">
                            <i class="fas fa-ticket-alt"></i>
                            <span>{{ $is_past ? 'Completed Event' : 'Community Gathering' }}</span>
                        </div>
                        
                        <h3 class="text-4xl font-black text-slate-900 mb-6 tracking-tight uppercase group-hover:text-primary transition-colors">
                            {{ $event->title }}
                        </h3>

                        <div class="flex items-center text-slate-500 font-bold text-sm mb-8 gap-6">
                            <span><i class="fas fa-map-marker-alt mr-2 text-secondary"></i> {{ $event->location }}</span>
                        </div>

                        <p class="text-slate-500 leading-relaxed mb-10 line-clamp-2 italic">
                            {{ strip_tags($event->description) }}
                        </p>

                        <div class="flex items-center gap-4">
                            @if(!$is_past)
                                <a href="{{ route('events.show', $event->id) }}" class="bg-primary hover:bg-slate-900 text-white px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20 transition-all transform hover:-translate-y-1">
                                    Book Tickets Now
                                </a>
                            @else
                                <a href="{{ route('gallery') }}" class="border-2 border-slate-200 text-slate-400 px-10 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-slate-50 transition-all">
                                    View Event Gallery
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-32 text-center bg-white rounded-[3rem] border border-dashed border-slate-200 shadow-xl">
                    <i class="fas fa-calendar-alt text-7xl text-slate-100 mb-8"></i>
                    <h3 class="text-slate-400 font-black uppercase tracking-[0.3em]">Quiet Period</h3>
                    <p class="text-slate-400 mt-4 font-medium uppercase text-xs tracking-widest">No scheduled events at the moment. Check back soon!</p>
                </div>
            @endforelse
        </div>

        @if($events->hasPages())
            <div class="mt-20">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</section>

<!-- Stats Callout -->
<section class="py-24 bg-white border-t border-slate-50">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h3 class="text-sm font-black text-slate-300 uppercase tracking-[0.5em] mb-12 italic">Join our vibrant community</h3>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-12">
            <div>
                <p class="text-5xl font-black text-slate-900 mb-2 tracking-tighter">50+</p>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-primary">Annual Events</p>
            </div>
            <div>
                <p class="text-5xl font-black text-slate-900 mb-2 tracking-tighter">10k+</p>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-primary">Attendees</p>
            </div>
            <div>
                <p class="text-5xl font-black text-slate-900 mb-2 tracking-tighter">100%</p>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-primary">Safe Environment</p>
            </div>
            <div>
                <p class="text-5xl font-black text-slate-900 mb-2 tracking-tighter">24/7</p>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-primary">Support</p>
            </div>
        </div>
    </div>
</section>
@endsection
