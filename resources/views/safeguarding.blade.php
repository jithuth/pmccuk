@extends('layouts.app')

@section('title', 'Safeguarding Policy | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Hero Header -->
<section class="py-24 bg-white border-b border-slate-200 overflow-hidden relative">
    <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full filter blur-3xl opacity-70"></div>
    <div class="max-w-4xl mx-auto px-4 relative z-10">
        <span class="text-primary font-black uppercase tracking-[0.4em] text-xs mb-6 block italic">Protection Policies</span>
        <h1 class="text-5xl md:text-6xl font-black text-slate-900 mb-6 leading-none">Safeguarding Policy</h1>
        <p class="text-slate-500 font-medium text-lg">Last updated: {{ date('F Y') }}</p>
    </div>
</section>

<!-- Content Area -->
<section class="py-20 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white p-12 md:p-16 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 prose prose-slate max-w-none">
            {!! $settings['legal_safeguarding'] ?? '
            <p class="lead">PMCC is committed to safeguarding children, young people, and vulnerable adults. We ensure all activities involving children are conducted with proper supervision, DBS-checked volunteers where necessary, and compliance with local safeguarding guidelines.</p>
            <h3>1. Child Safety</h3>
            <p>During all cultural and community events, children must remain under primary parental supervision unless registered in designated supervised children activities.</p>
            <h3>2. DBS Verifications</h3>
            <p>Our volunteers working directly with children are subject to appropriate Disclosure and Barring Service (DBS) checks in compliance with UK laws.</p>
            ' !!}
        </div>
    </div>
</section>
@endsection
