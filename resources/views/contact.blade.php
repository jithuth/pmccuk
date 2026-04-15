@extends('layouts.app')

@section('title', 'Get In Touch | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<section class="py-24 bg-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-20 items-center">
            <!-- Contact Info -->
            <div class="reveal">
                <span class="text-xs font-black uppercase tracking-[0.3em] text-secondary mb-6 block">Contact Us</span>
                <h2 class="text-5xl font-extrabold text-slate-900 mb-8 leading-tight">We're here to support our community.</h2>
                <p class="text-slate-500 text-lg mb-12 leading-relaxed">Have a question about membership, upcoming events, or student support? Reach out to our dedicated committee members.</p>
                
                <div class="space-y-8">
                    <div class="flex gap-6 items-start">
                        <div class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center text-primary text-xl flex-shrink-0">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-1">Email Support</p>
                            <p class="text-lg font-bold text-slate-900">{{ $settings['contact_email'] ?? 'info@pmcc-uk.org' }}</p>
                        </div>
                    </div>
                    <div class="flex gap-6 items-start">
                        <div class="w-14 h-14 bg-secondary/10 rounded-2xl flex items-center justify-center text-secondary text-xl flex-shrink-0">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-1">Call Us</p>
                            <p class="text-lg font-bold text-slate-900">{{ $settings['contact_phone'] ?? '+44 123 456 7890' }}</p>
                        </div>
                    </div>
                    <div class="flex gap-6 items-start">
                        <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center text-amber-600 text-xl flex-shrink-0">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-1">Location</p>
                            <p class="text-lg font-bold text-slate-900">{{ $settings['contact_address'] ?? 'Plymouth, United Kingdom' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="relative">
                <div class="absolute inset-0 bg-primary/5 rounded-[3rem] -rotate-3 scale-105"></div>
                <div class="relative bg-white p-12 rounded-[3.5rem] shadow-2xl shadow-slate-200/50 border border-slate-50">
                    <form action="{{ url('contact-submit') }}" method="POST" class="space-y-6">
                        @csrf
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2 ms-2">Full Name</label>
                                <input type="text" name="name" required class="w-full px-6 py-4 rounded-3xl bg-slate-50 border-0 focus:ring-4 focus:ring-primary/10 text-slate-900 font-medium transition" placeholder="John Doe">
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2 ms-2">Email Address</label>
                                <input type="email" name="email" required class="w-full px-6 py-4 rounded-3xl bg-slate-50 border-0 focus:ring-4 focus:ring-primary/10 text-slate-900 font-medium transition" placeholder="john@example.com">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2 ms-2">Subject</label>
                            <select name="subject" class="w-full px-6 py-4 rounded-3xl bg-slate-50 border-0 focus:ring-4 focus:ring-primary/10 text-slate-900 font-medium transition">
                                <option>General Inquiry</option>
                                <option>Membership Question</option>
                                <option>Sponsorship Opportunity</option>
                                <option>Student Support</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-400 mb-2 ms-2">Your Message</label>
                            <textarea name="message" required rows="5" class="w-full px-6 py-4 rounded-3xl bg-slate-50 border-0 focus:ring-4 focus:ring-primary/10 text-slate-900 font-medium transition" placeholder="How can we help you?"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-primary text-white py-5 rounded-[2rem] font-black uppercase tracking-widest hover:bg-zinc-800 transition shadow-xl">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
