@extends('layouts.app')

@section('title', 'Privacy Policy | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Hero Header -->
<section class="py-24 bg-white border-b border-slate-200 overflow-hidden relative">
    <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full filter blur-3xl opacity-70"></div>
    <div class="max-w-4xl mx-auto px-4 relative z-10">
        <span class="text-primary font-black uppercase tracking-[0.4em] text-xs mb-6 block italic">Legal Agreements</span>
        <h1 class="text-5xl md:text-6xl font-black text-slate-900 mb-6 leading-none">Privacy Policy</h1>
        <p class="text-slate-500 font-medium text-lg">Last updated: {{ date('F Y') }}</p>
    </div>
</section>

<!-- Content Area -->
<section class="py-20 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white p-12 md:p-16 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 prose prose-slate max-w-none">
            {!! $settings['legal_privacy_policy'] ?? '
            <p class="lead font-medium text-slate-700 mb-8">The PMCC website has been developed following industry standard and military grade security practices and employs multiple layers of protection to safeguard member information.</p>
            <h3 class="text-2xl font-black text-slate-900 mt-10 mb-6 uppercase tracking-tight">Our security measures include:</h3>
            <ul class="space-y-4 text-slate-600 list-disc pl-6 leading-relaxed font-medium">
                <li><strong>HTTPS Encryption:</strong> TLS 1.3 is enforced for all communication to ensure data in transit is encrypted.</li>
                <li><strong>Sensitive Data Protection:</strong> AES-256 encryption is used for sensitive member information stored within the database.</li>
                <li><strong>Secure Hashing:</strong> Passwords are secured using Argon2 hashing algorithm.</li>
                <li><strong>Multi-Factor Security:</strong> Two-Factor Authentication (2FA) is implemented for all administrative accounts.</li>
                <li><strong>Access Control:</strong> Role-Based Access Control (RBAC) enforced with the Principle of Least Privilege.</li>
                <li><strong>Vulnerability Safeguards:</strong> Protection against SQL Injection, Cross-Site Scripting (XSS), Cross-Site Request Forgery (CSRF), Session Hijacking, and Remote Code Execution (RCE) attacks.</li>
                <li><strong>Input Integrity:</strong> Strict input validation, output encoding, and parameterized database queries throughout the application.</li>
                <li><strong>Secure Session Management:</strong> Cookie safeguards using HttpOnly, Secure, and SameSite attributes, with automatic timeout for inactive administrative sessions.</li>
                <li><strong>Infrastructure Protection:</strong> Firewall and Web Application Firewall (WAF) protection, DDoS mitigation, and continuous security patching and software updates.</li>
                <li><strong>Activity Monitoring:</strong> Security event logging, audit trails, and continuous monitoring for suspicious activities.</li>
                <li><strong>Backups & Recovery:</strong> Regular encrypted backups with controlled restoration procedures.</li>
            </ul>
            ' !!}
        </div>
    </div>
</section>
@endsection
