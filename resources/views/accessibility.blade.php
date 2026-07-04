@extends('layouts.app')

@section('title', 'Accessibility Statement | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Hero Header -->
<section class="py-24 bg-white border-b border-slate-200 overflow-hidden relative">
    <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full filter blur-3xl opacity-70"></div>
    <div class="max-w-4xl mx-auto px-4 relative z-10">
        <span class="text-primary font-black uppercase tracking-[0.4em] text-xs mb-6 block italic">Universal Access</span>
        <h1 class="text-5xl md:text-6xl font-black text-slate-900 mb-6 leading-none">Accessibility Statement</h1>
        <p class="text-slate-500 font-medium text-lg">Last updated: {{ date('F Y') }}</p>
    </div>
</section>

<!-- Content Area -->
<section class="py-20 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white p-12 md:p-16 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 prose prose-slate max-w-none">
            {!! $settings['legal_accessibility'] ?? '
            <p class="lead">PMCC is committed to making its website and cultural events accessible to everyone, including individuals with disabilities. We strive to improve web accessibility in accordance with WCAG 2.1 guidelines.</p>
            <h3>1. Web Design</h3>
            <p>Our website utilizes semantic HTML structure, proper ARIA tags, and high-contrast color balances to accommodate screen readers and assistive devices.</p>
            <h3>2. Event Accommodations</h3>
            <p>We work to secure event venues that accommodate physical access and provide support requirements for members when requested.</p>
            ' !!}
        </div>
    </div>
</section>
@endsection
