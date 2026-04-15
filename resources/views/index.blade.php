@extends('layouts.app')

@section('content')
    <!-- Hero Section -->
    <section class="relative h-[500px] bg-slate-900 overflow-hidden">
        <img src="{{ !empty($settings['hero_image']) ? asset('storage/' . $settings['hero_image']) : asset('assets/img/pmcc_logo.png') }}"
            alt="Banner" class="absolute inset-0 w-full h-full object-cover opacity-60">
        <div class="absolute inset-0 bg-gradient-to-r from-primary/80 to-transparent"></div>
        <div class="max-w-7xl mx-auto px-4 h-full flex items-center relative z-10">
            <div class="max-w-4xl text-white">
                <h1 class="text-6xl font-extrabold mb-6 leading-tight tracking-tight">
                    {!! nl2br(e($settings['hero_title'] ?? "Welcome to \nPlymouth Malayalee Community")) !!}
                </h1>
                <p class="text-xl text-slate-100 mb-8 leading-relaxed">
                    {{ $settings['hero_desc'] ?? 'Uniting Malayalees in Plymouth through culture, heritage, and community support since our inception.' }}
                </p>
                <div class="flex space-x-4">
                    <a href="{{ url('membership') }}"
                        class="bg-secondary px-8 py-3 rounded font-bold hover:bg-zinc-700 transition">Get Membership</a>
                    <a href="{{ url('about') }}"
                        class="bg-white text-primary px-8 py-3 rounded font-bold hover:bg-slate-100 transition">About
                        PMCC-UK</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest News & Announcements Bar -->
    <div class="bg-secondary text-white py-3 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 flex items-center">
            <span
                class="bg-white text-secondary px-3 py-1 rounded-sm text-xs font-bold mr-4 uppercase whitespace-nowrap">News
                Ticker</span>
            <marquee behavior="scroll" direction="left" class="text-sm font-medium">
                {{ $settings['ticker_text'] ?? 'Welcome to the new PMCC-UK website! Membership registration for 2026 is now open. Join us for our upcoming cultural events.' }}
            </marquee>
        </div>
    </div>

    <!-- Main Content Grid -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid lg:grid-cols-3 gap-12">

                <!-- Welcome Message -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="border-b-2 border-primary inline-block pb-2 mb-4">
                        <h2 class="text-3xl font-bold text-primary italic">A Message from our President</h2>
                    </div>
                    <div class="flex flex-col md:flex-row gap-8 items-start">
                        <img src="{{ !empty($settings['pres_image']) ? asset('storage/' . $settings['pres_image']) : asset('assets/img/pmcc_logo.png') }}"
                            class="w-full md:w-48 rounded-lg shadow-md border-4 border-slate-50">
                        <div class="text-slate-600 leading-relaxed space-y-4 text-justify">
                            {!! nl2br(e($settings['pres_msg'] ?? "Dear Friends and Community Members, It is a great honor to welcome you to our official portal...")) !!}
                            <p class="font-bold text-primary">With warm
                                regards,<br>{{ $settings['pres_name'] ?? 'The President, PMCC-UK' }}</p>
                        </div>
                    </div>

                    <!-- Featured Boxes -->
                    <div class="grid md:grid-cols-3 gap-6 mt-12">
                        <div class="bg-slate-50 p-8 border-l-4 border-primary rounded-r-lg shadow-sm reveal">
                            <i class="fas fa-hand-holding-heart text-3xl text-primary mb-4"></i>
                            <h3 class="text-xl font-bold mb-3">Community Support</h3>
                            <p class="text-xs text-slate-500 font-medium leading-relaxed">We provide essential support to
                                newcomers and existing members in Plymouth.</p>
                        </div>
                        <div class="bg-slate-50 p-8 border-l-4 border-secondary rounded-r-lg shadow-sm reveal">
                            <i class="fas fa-music text-3xl text-secondary mb-4"></i>
                            <h3 class="text-xl font-bold mb-3">Cultural Events</h3>
                            <p class="text-xs text-slate-500 font-medium leading-relaxed">From Onam to Christmas, we
                                celebrate all major festivals with traditional grandeur.</p>
                        </div>
                        <div class="bg-slate-50 p-8 border-l-4 border-amber-500 rounded-r-lg shadow-sm reveal">
                            <i class="fas fa-graduation-cap text-3xl text-amber-500 mb-4"></i>
                            <h3 class="text-xl font-bold mb-3">Student Corner</h3>
                            <p class="text-xs text-slate-500 font-medium leading-relaxed">Dedicated university support,
                                accommodation guidance, and networking.</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-10">
                    <div class="bg-slate-50 rounded-lg overflow-hidden border border-slate-200">
                        <div class="bg-primary text-white px-6 py-4">
                            <h3 class="text-lg font-bold flex items-center"><i class="fas fa-calendar-alt mr-2"></i>
                                Upcoming Events</h3>
                        </div>
                        <div class="p-6 space-y-6">
                            @forelse ($events as $e)
                                <div class="flex gap-4 border-b border-slate-200 pb-4">
                                    <div
                                        class="bg-white border-2 border-primary rounded text-center min-w-[60px] h-[60px] flex flex-col justify-center">
                                        <span
                                            class="text-xs font-bold text-slate-400 text-uppercase">{{ date('M', strtotime($e->event_date)) }}</span>
                                        <span
                                            class="text-xl font-black text-primary leading-none">{{ date('d', strtotime($e->event_date)) }}</span>
                                    </div>
                                    <div>
                                        <h4
                                            class="font-bold text-slate-800 text-sm hover:text-primary cursor-pointer transition">
                                            {{ $e->title }}</h4>
                                        <p class="text-xs text-slate-500 mt-1"><i class="fas fa-map-marker-alt"></i>
                                            {{ $e->location }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <p class="text-sm text-slate-500 font-medium">No upcoming events</p>
                                </div>
                            @endforelse
                            <a href="{{ url('events') }}"
                                class="block text-center text-primary text-sm font-bold uppercase hover:underline">View All
                                Events</a>
                        </div>
                    </div>

                    <!-- Newsletter -->
                    <div class="bg-primary rounded-lg p-8 text-white text-center">
                        <h3 class="text-xl font-bold mb-4">Newsletter</h3>
                        <form id="newsletter-form" class="space-y-4">
                            @csrf
                            <input type="email" name="email" placeholder="Email Address" required
                                class="w-full px-4 py-3 rounded bg-white/10 border border-white/20 text-white placeholder:text-slate-400 outline-none">
                            <button type="submit"
                                class="w-full bg-secondary py-3 rounded font-bold uppercase tracking-wider text-sm hover:bg-zinc-800 transition">Subscribe</button>
                        </form>
                        <div id="newsletter-msg" class="mt-4 text-xs font-bold hidden"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Stats -->
    <section class="bg-slate-100 py-16">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <span class="text-4xl font-black text-primary">{{ $settings['stat_members'] ?? '500+' }}</span>
                    <p class="text-slate-500 font-bold mt-2 uppercase text-xs tracking-widest">Members</p>
                </div>
                <div class="text-center">
                    <span class="text-4xl font-black text-primary">{{ $settings['stat_committees'] ?? '22' }}</span>
                    <p class="text-slate-500 font-bold mt-2 uppercase text-xs tracking-widest">Committees</p>
                </div>
                <div class="text-center">
                    <span class="text-4xl font-black text-primary">{{ $settings['stat_events'] ?? '150' }}</span>
                    <p class="text-slate-500 font-bold mt-2 uppercase text-xs tracking-widest">Events</p>
                </div>
                <div class="text-center">
                    <span class="text-4xl font-black text-primary">{{ $settings['stat_years'] ?? '12' }}</span>
                    <p class="text-slate-500 font-bold mt-2 uppercase text-xs tracking-widest">Years</p>
                </div>
            </div>
        </div>
    </section>
@endsection