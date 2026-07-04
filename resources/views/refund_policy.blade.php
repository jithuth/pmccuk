@extends('layouts.app')

@section('title', 'Refund & Cancellation Policy | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Hero Header -->
<section class="py-24 bg-white border-b border-slate-200 overflow-hidden relative">
    <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full filter blur-3xl opacity-70"></div>
    <div class="max-w-4xl mx-auto px-4 relative z-10">
        <span class="text-primary font-black uppercase tracking-[0.4em] text-xs mb-6 block italic">Refund Policies</span>
        <h1 class="text-5xl md:text-6xl font-black text-slate-900 mb-6 leading-none">Refund & Cancellation</h1>
        <p class="text-slate-500 font-medium text-lg">Last updated: {{ date('F Y') }}</p>
    </div>
</section>

<!-- Content Area -->
<section class="py-20 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white p-12 md:p-16 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 prose prose-slate max-w-none">
            {!! $settings['legal_refund_cancellation'] ?? '
            <p class="lead">Tickets purchased for PMCC events are non-refundable except in cases where the event is cancelled or rescheduled. Under exceptional circumstances, refund requests submitted 7 days prior to the event may be considered by the committee.</p>
            <h3>1. Event Cancellations</h3>
            <p>If an event organized by PMCC is cancelled, ticket holders will be offered a full refund or option to transfer ticket value to a future rescheduled event.</p>
            <h3>2. Committee Discretion</h3>
            <p>Exceptional refund requests due to emergency situations must be submitted in writing to the committee via our official contact channel at least 7 days before the event starts.</p>
            ' !!}
        </div>
    </div>
</section>
@endsection
