@extends('layouts.app')

@section('title', 'Member Code of Conduct | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Hero Header -->
<section class="py-24 bg-white border-b border-slate-200 overflow-hidden relative">
    <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full filter blur-3xl opacity-70"></div>
    <div class="max-w-4xl mx-auto px-4 relative z-10">
        <span class="text-primary font-black uppercase tracking-[0.4em] text-xs mb-6 block italic">Behavior Standards</span>
        <h1 class="text-5xl md:text-6xl font-black text-slate-900 mb-6 leading-none">Code of Conduct</h1>
        <p class="text-slate-500 font-medium text-lg">Last updated: {{ date('F Y') }}</p>
    </div>
</section>

<!-- Content Area -->
<section class="py-20 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white p-12 md:p-16 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 prose prose-slate max-w-none">
            {!! $settings['legal_code_of_conduct'] ?? '
            <p class="lead">Members are expected to treat all other community members, volunteers, and guests with respect, dignity, and inclusivity. Harassment, discrimination, or abusive behaviour during events or on community platforms will result in termination of membership.</p>
            <h3>1. Mutual Respect</h3>
            <p>We celebrate diversity and cultural unity. Discrimination based on gender, race, religion, or background is strictly prohibited.</p>
            <h3>2. Events Behaviour</h3>
            <p>Members must maintain high standards of social responsibility during all community gatherings, ensuring a safe environment for all families and participants.</p>
            ' !!}
        </div>
    </div>
</section>
@endsection
