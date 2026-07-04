@extends('layouts.app')

@section('title', 'Cookie Policy | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Hero Header -->
<section class="py-24 bg-white border-b border-slate-200 overflow-hidden relative">
    <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full filter blur-3xl opacity-70"></div>
    <div class="max-w-4xl mx-auto px-4 relative z-10">
        <span class="text-primary font-black uppercase tracking-[0.4em] text-xs mb-6 block italic">Legal Agreements</span>
        <h1 class="text-5xl md:text-6xl font-black text-slate-900 mb-6 leading-none">Cookie Policy</h1>
        <p class="text-slate-500 font-medium text-lg">Last updated: {{ date('F Y') }}</p>
    </div>
</section>

<!-- Content Area -->
<section class="py-20 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white p-12 md:p-16 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 prose prose-slate max-w-none">
            {!! $settings['legal_cookie_policy'] ?? '
            <p class="lead">Our system uses cookies solely for authentication and session management to ensure a smooth administrative experience.</p>
            <h3>1. What are cookies?</h3>
            <p>Cookies are small text files placed on your device to store data that can be recalled by a web server in the domain that placed the cookie.</p>
            <h3>2. How we use cookies</h3>
            <p>We use session cookies and authentication cookies to maintain your login status and ensure security across our platform. These cookies are strictly necessary for the system to function.</p>
            ' !!}
        </div>
    </div>
</section>
@endsection
