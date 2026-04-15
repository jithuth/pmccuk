@extends('layouts.app')

@section('title', 'Student Recruitment & Corner | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Hero -->
<section class="py-24 bg-zinc-950 text-white relative overflow-hidden">
    <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-primary/20 to-transparent"></div>
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <div class="max-w-3xl">
            <span class="inline-block bg-primary text-white text-[10px] font-black uppercase tracking-widest px-4 py-1 rounded-full mb-8">Future Leaders</span>
            <h1 class="text-6xl font-black mb-8 leading-tight">Empowering Our <span class="text-primary">Malayalee Students</span> in Plymouth.</h1>
            <p class="text-xl text-slate-400 mb-10 leading-relaxed font-medium">New to Plymouth? We provide dedicated support for university admissions, accommodation, networking, and cultural integration for the student community.</p>
            <div class="flex flex-wrap gap-4">
                <a href="#register" class="bg-primary px-10 py-4 rounded-full font-black uppercase tracking-widest hover:bg-white hover:text-primary transition shadow-2xl">Register as Student</a>
                <a href="{{ url('contact') }}" class="bg-white/10 backdrop-blur-md px-10 py-4 rounded-full font-black uppercase tracking-widest hover:bg-white/20 transition">Get Guidance</a>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid md:grid-cols-3 gap-12">
            <div class="bg-slate-50 p-12 rounded-[2.5rem] hover:-translate-y-2 transition-transform duration-500">
                <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600 text-2xl mb-8">
                    <i class="fas fa-university"></i>
                </div>
                <h3 class="text-2xl font-bold text-slate-900 mb-4">Uni Guidance</h3>
                <p class="text-slate-500 leading-relaxed">Assistance with university enrollment, academic support, and navigating campus life in Plymouth.</p>
            </div>
            <div class="bg-slate-50 p-12 rounded-[2.5rem] hover:-translate-y-2 transition-transform duration-500">
                <div class="w-16 h-16 bg-amber-100 rounded-2xl flex items-center justify-center text-amber-600 text-2xl mb-8">
                    <i class="fas fa-home"></i>
                </div>
                <h3 class="text-2xl font-bold text-slate-900 mb-4">Accommodation</h3>
                <p class="text-slate-500 leading-relaxed">Find safe and affordable housing near your campus with verified community recommendations.</p>
            </div>
            <div class="bg-slate-50 p-12 rounded-[2.5rem] hover:-translate-y-2 transition-transform duration-500">
                <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center text-emerald-600 text-2xl mb-8">
                    <i class="fas fa-users-class"></i>
                </div>
                <h3 class="text-2xl font-bold text-slate-900 mb-4">Networking</h3>
                <p class="text-slate-500 leading-relaxed">Connect with senior students and professionals to unlock career opportunities and mentorship.</p>
            </div>
        </div>
    </div>
</section>

<!-- Register Form -->
<section id="register" class="py-24 bg-slate-50 border-y">
    <div class="max-w-7xl mx-auto px-4 text-center mb-16">
        <h2 class="text-4xl font-black text-slate-900 uppercase">Join the Student Network</h2>
        <div class="h-2 w-24 bg-primary mx-auto mt-6"></div>
    </div>
    <div class="max-w-3xl mx-auto px-4">
        <div class="bg-white p-12 rounded-[3.5rem] shadow-2xl shadow-slate-200 border border-slate-100">
            <form action="{{ url('student-submit') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-2 ms-2 font-black italic">Full Name</label>
                        <input type="text" name="full_name" required class="w-full px-6 py-4 rounded-2xl bg-slate-50 border-0 focus:ring-4 focus:ring-primary/10 transition" placeholder="Abhilash Nair">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-2 ms-2 font-black italic">Phone Number</label>
                        <input type="text" name="phone" required class="w-full px-6 py-4 rounded-2xl bg-slate-50 border-0 focus:ring-4 focus:ring-primary/10 transition" placeholder="+44 7XXX XXXXXX">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-black uppercase text-slate-400 mb-2 ms-2 font-black italic">Email Address</label>
                    <input type="email" name="email" required class="w-full px-6 py-4 rounded-2xl bg-slate-50 border-0 focus:ring-4 focus:ring-primary/10 transition" placeholder="abhilash@uni.ac.uk">
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-2 ms-2 font-black italic">University Name</label>
                        <input type="text" name="university" required class="w-full px-6 py-4 rounded-2xl bg-slate-50 border-0 focus:ring-4 focus:ring-primary/10 transition" placeholder="University of Plymouth">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-2 ms-2 font-black italic">Year of Study</label>
                        <select name="study_year" class="w-full px-6 py-4 rounded-2xl bg-slate-50 border-0 focus:ring-4 focus:ring-primary/10 transition">
                            <option>Foundation</option>
                            <option>Year 1</option>
                            <option>Year 2</option>
                            <option>Year 3 / Final</option>
                            <option>Postgraduate / Masters</option>
                            <option>PHD / Research</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="w-full bg-primary text-white py-5 rounded-2xl font-black uppercase tracking-widest hover:bg-zinc-800 transition shadow-xl mt-4">Submit Registration</button>
            </form>
        </div>
    </div>
</section>
@endsection
