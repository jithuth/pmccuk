@extends('layouts.app')

@section('title', $event->title . ' | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Unique Immersive Hero Header -->
<section class="relative min-h-[60vh] flex items-end pb-20 overflow-hidden bg-slate-900">
    <!-- Visual Backdrop -->
    <div class="absolute top-0 left-0 w-full h-full">
        <img src="{{ $event->image_url }}" 
            class="w-full h-full object-cover opacity-40 scale-105 blur-sm">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 w-full relative z-10">
        <a href="{{ route('events') }}" class="inline-flex items-center text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 hover:text-primary mb-12 transition-all group">
            <i class="fas fa-arrow-left mr-3 group-hover:-translate-x-2 transition-transform"></i> Return to Events
        </a>

        <div class="max-w-4xl">
            <span class="inline-block px-4 py-1.5 bg-primary/20 text-primary rounded-full text-[10px] font-black uppercase tracking-[0.3em] mb-6 border border-primary/20">
                Community Celebration
            </span>
            <h1 class="text-7xl font-black text-white mb-8 leading-[1.1] tracking-tighter uppercase italic">
                {{ $event->title }}
            </h1>
            
            <div class="flex flex-wrap items-center gap-10 text-slate-300 font-bold text-sm uppercase tracking-widest">
                <span class="flex items-center"><i class="fas fa-calendar-day mr-3 text-primary text-xl"></i> {{ date('l, F d, Y', strtotime($event->event_date)) }}</span>
                <span class="flex items-center"><i class="fas fa-map-marker-alt mr-3 text-primary text-xl"></i> {{ $event->location }}</span>
            </div>
        </div>
    </div>
</section>

<!-- Content Grid -->
<section class="py-24 bg-[#f8fafc]">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-16">
            
            <!-- Left Side: Main Content -->
            <div class="lg:col-span-8">
                <!-- Main Image Card -->
                <div class="bg-white p-4 rounded-[3.5rem] shadow-2xl shadow-slate-200/50 mb-16 border border-slate-50 overflow-hidden group">
                    <img src="{{ $event->image_url }}" 
                        class="w-full h-auto rounded-[2.5rem] shadow-inner transition-transform duration-700 group-hover:scale-[1.02]">
                </div>

                <div class="bg-white rounded-[3rem] p-12 lg:p-16 shadow-xl border border-slate-50 relative overflow-hidden">
                    <!-- Subtle background decoration -->
                    <div class="absolute top-0 right-0 p-12 opacity-[0.03] pointer-events-none">
                        <i class="fas fa-quote-right text-9xl"></i>
                    </div>

                    <h2 class="text-3xl font-black text-slate-900 mb-10 flex items-center uppercase tracking-tight">
                        <span class="w-12 h-1.5 bg-gradient-to-r from-primary to-secondary mr-6 rounded-full"></span>
                        Event Description
                    </h2>
                    
                    <div class="prose prose-xl text-slate-600 leading-[1.8] text-left font-medium max-w-none prose-p:mb-8 whitespace-pre-line">
                        {!! $event->description !!}
                    </div>
                </div>

                <!-- Shared Moments Section -->
                <div class="mt-16 bg-slate-900 rounded-[3rem] p-12 text-white relative overflow-hidden">
                    <div class="flex items-center justify-between relative z-10">
                        <div>
                            <h4 class="text-2xl font-black mb-2 italic">Capture the Memories</h4>
                            <p class="text-slate-400 text-sm font-bold uppercase tracking-widest">This event will be archived in our gallery</p>
                        </div>
                        <i class="fas fa-camera-retro text-5xl text-primary/40"></i>
                    </div>
                </div>
            </div>

            <!-- Right Side: Sticky Action Sidebar -->
            <div class="lg:col-span-4">
                <div class="sticky top-12 space-y-8">
                    
                    <!-- Booking Card -->
                    <div class="bg-white rounded-[3rem] p-10 shadow-2xl border border-slate-50 text-center relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-2 bg-primary"></div>
                        
                        <div class="w-20 h-20 bg-primary/10 text-primary rounded-full flex items-center justify-center text-3xl mx-auto mb-8 shadow-inner">
                            <i class="fas fa-ticket-alt"></i>
                        </div>

                        <h3 class="text-2xl font-black text-slate-900 mb-4 tracking-tight uppercase">Secure Your Spot</h3>
                        <p class="text-slate-500 text-sm font-medium leading-relaxed mb-10">Join us for this unforgettable experience. Tickets are limited, so reserve yours today!</p>
                        
                        <a href="{{ route('event.book', $event->id) }}" class="flex items-center justify-center w-full bg-primary hover:bg-slate-900 text-white font-black uppercase tracking-widest text-xs py-6 rounded-2xl shadow-xl shadow-primary/20 transition-all transform hover:-translate-y-1 active:scale-[0.98]">
                            Register Now <i class="fas fa-chevron-right ml-4"></i>
                        </a>

                        <div class="mt-8 flex items-center justify-center gap-6 text-[10px] font-black text-slate-300 uppercase tracking-widest">
                            <span class="flex items-center"><i class="fas fa-check-circle text-green-400 mr-2"></i> Instant OTP</span>
                            <span class="flex items-center"><i class="fas fa-shield-alt text-blue-400 mr-2"></i> Secure Transfer</span>
                        </div>
                    </div>

                    <!-- Event Quick Facts -->
                    <div class="bg-slate-50 rounded-[2.5rem] p-10 border border-slate-100">
                        <h5 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-8 border-b border-slate-200 pb-4">Event Logistics</h5>
                        
                        <div class="space-y-8">
                            <div class="flex items-start">
                                <div class="w-10 h-10 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center justify-center text-primary mr-4 shrink-0">
                                    <i class="fas fa-clock text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-black tracking-widest text-slate-400 mb-1">Timezone</p>
                                    <p class="font-bold text-slate-900">London (GMT+0)</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-10 h-10 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center justify-center text-primary mr-4 shrink-0">
                                    <i class="fas fa-users text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-black tracking-widest text-slate-400 mb-1">Capacity</p>
                                    <p class="font-bold text-slate-900">Limited Availability</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="w-10 h-10 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center justify-center text-primary mr-4 shrink-0">
                                    <i class="fas fa-info-circle text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase font-black tracking-widest text-slate-400 mb-1">Status</p>
                                    <p class="font-bold text-green-500 uppercase">Open for Booking</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Share Section -->
                    <div class="text-center py-6">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 italic">Invite your friends</p>
                        <div class="flex justify-center gap-4">
                            <a href="#" class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:border-blue-600 transition-all"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-400 hover:text-blue-400 hover:border-blue-400 transition-all"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="w-10 h-10 rounded-full border border-slate-200 flex items-center justify-center text-slate-400 hover:text-green-500 hover:border-green-500 transition-all"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
