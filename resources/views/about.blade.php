@extends('layouts.app')

@section('title', 'About Us | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Hero / Story Section -->
<section class="py-24 bg-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-20 items-center">
            <div class="relative">
                <div class="absolute -top-10 -left-10 w-72 h-72 bg-primary/5 rounded-full filter blur-3xl opacity-70"></div>
                <div class="absolute -bottom-10 -right-10 w-72 h-72 bg-secondary/5 rounded-full filter blur-3xl opacity-70"></div>
                
                <div class="relative">
                    <img src="{{ !empty($settings['about_image']) ? asset('storage/' . $settings['about_image']) : 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?q=80&w=1000' }}" 
                        class="rounded-[4rem] shadow-2xl border-8 border-slate-50 relative z-10 w-full object-cover aspect-[4/5]">
                    
                    <div class="absolute -bottom-10 -left-10 bg-white p-10 rounded-[3rem] shadow-2xl border border-slate-100 z-20 flex items-center gap-6">
                        <div class="w-20 h-20 bg-primary/10 rounded-3xl flex items-center justify-center text-primary text-3xl font-black">
                            12+
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-1">Legacy of</p>
                            <p class="text-xl font-black text-slate-900 leading-none">Unity & Culture</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="reveal">
                <span class="text-primary font-black uppercase tracking-[0.4em] text-xs mb-6 block italic">Our Heritage</span>
                <h1 class="text-6xl font-black text-slate-900 mb-10 leading-[1.1]">
                    {{ $settings['about_title'] ?? 'The Heart of Malayalees in Plymouth' }}
                </h1>
                <div class="text-slate-600 space-y-8 leading-relaxed text-lg font-medium text-justify">
                    {!! nl2br(e($settings['about_content'] ?? 'PMCC-UK (Plymouth Malayalee Cultural Community) is a non-profit cultural organization dedicated to preserving our rich heritage and supporting our members in the UK.')) !!}
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Vision & Mission -->
<section class="py-24 bg-slate-50 border-y border-slate-200">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white p-16 rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 group transition-all duration-500 hover:shadow-2xl">
                <div class="w-20 h-20 bg-primary rounded-3xl flex items-center justify-center text-white text-3xl mb-10 shadow-lg group-hover:rotate-6 transition-transform">
                    <i class="fas fa-eye"></i>
                </div>
                <h3 class="text-3xl font-black text-slate-900 mb-6 uppercase tracking-tight">{{ $settings['about_vision_title'] ?? 'Our Vision' }}</h3>
                <p class="text-slate-500 leading-relaxed text-lg">{{ $settings['about_vision_content'] ?? 'To be a leading community organization that empowers its members and promotes our cultural identity.' }}</p>
            </div>
            
            <div class="bg-white p-16 rounded-[3.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 group transition-all duration-500 hover:shadow-2xl">
                <div class="w-20 h-20 bg-secondary rounded-3xl flex items-center justify-center text-white text-3xl mb-10 shadow-lg group-hover:-rotate-6 transition-transform">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h3 class="text-3xl font-black text-slate-900 mb-6 uppercase tracking-tight">{{ $settings['about_mission_title'] ?? 'Our Mission' }}</h3>
                <p class="text-slate-500 leading-relaxed text-lg">{{ $settings['about_mission_content'] ?? 'To foster unity, celebrate our rich cultural heritage, and support our community members through collaborative initiatives.' }}</p>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="py-24 bg-white relative">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-20">
            <span class="text-xs font-black uppercase tracking-[0.4em] text-primary mb-4 block">Core Principles</span>
            <h2 class="text-5xl font-black text-slate-900 uppercase">What Defines Us</h2>
            <div class="h-2 w-24 bg-secondary mx-auto mt-8"></div>
        </div>

        <div class="grid md:grid-cols-4 gap-12">
            <div class="text-center">
                <div class="text-4xl font-black text-primary/20 mb-4 italic">01</div>
                <h4 class="text-xl font-black text-slate-900 mb-4">Integrity</h4>
                <p class="text-sm text-slate-500 leading-relaxed font-medium">Transparent operations and dedicated service to our community members.</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-black text-primary/20 mb-4 italic">02</div>
                <h4 class="text-xl font-black text-slate-900 mb-4">Unity</h4>
                <p class="text-sm text-slate-500 leading-relaxed font-medium">Bridging the gap between generations and families in the UK.</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-black text-primary/20 mb-4 italic">03</div>
                <h4 class="text-xl font-black text-slate-900 mb-4">Excellence</h4>
                <p class="text-sm text-slate-500 leading-relaxed font-medium">Striving for perfection in every cultural celebration and event.</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-black text-primary/20 mb-4 italic">04</div>
                <h4 class="text-xl font-black text-slate-900 mb-4">Empowerment</h4>
                <p class="text-sm text-slate-500 leading-relaxed font-medium">Supporting students and new families to thrive in their new home.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-24 bg-primary text-white">
    <div class="max-w-5xl mx-auto px-4 text-center">
        <h2 class="text-4xl font-black mb-10 leading-tight">Join the growing PMCC family and preserve our rich cultural identity together.</h2>
        <a href="{{ url('membership') }}" class="inline-block bg-white text-primary px-12 py-5 rounded-full font-black uppercase tracking-widest hover:bg-secondary hover:text-white transition shadow-2xl">Become a Member</a>
    </div>
</section>
@endsection
