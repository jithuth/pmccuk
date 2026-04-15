@extends('layouts.app')

@section('title', 'Ticket Verification')

@section('content')
<div class="min-h-screen bg-slate-900 flex items-center justify-center py-8 px-4">
    <div class="max-w-md w-full bg-white rounded-[2.5rem] shadow-2xl overflow-hidden">
        @php
            $isValid = in_array($booking->booking_status, ['confirmed', 'approved']);
            $totalGuests = ($booking->adult_count ?? 0) + ($booking->child_count ?? 0) + ($booking->infant_count ?? 0);
            $alreadyScanned = $alreadyScanned ?? false;
        @endphp

        {{-- Status Header --}}
        @if(!$isValid)
            {{-- RED: Invalid / Pending --}}
            <div class="p-8 text-center bg-red-500 text-white">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-times-circle text-5xl"></i>
                </div>
                <h1 class="text-2xl font-black uppercase tracking-widest">Entry Denied</h1>
                <p class="text-red-100 text-xs mt-2 uppercase tracking-widest font-bold">Booking is Pending / Not Confirmed</p>
            </div>
        @elseif($alreadyScanned)
            {{-- YELLOW: Duplicate Scan Warning --}}
            <div class="p-8 text-center bg-amber-400 text-slate-900">
                <div class="w-20 h-20 bg-black/10 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-5xl"></i>
                </div>
                <h1 class="text-2xl font-black uppercase tracking-widest">Already Scanned!</h1>
                <p class="text-slate-800 text-xs mt-2 uppercase tracking-widest font-bold">This ticket has already been used for entry</p>
            </div>
        @else
            {{-- GREEN: Valid Entry - Just Checked In --}}
            <div class="p-8 text-center bg-green-500 text-white">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-5xl"></i>
                </div>
                <h1 class="text-2xl font-black uppercase tracking-widest">Entry Approved!</h1>
                <p class="text-green-100 text-xs mt-2 uppercase tracking-widest font-bold">Ticket verified & check-in recorded</p>
            </div>
        @endif

        <div class="p-8">
            {{-- Event Info --}}
            <div class="mb-6 pb-6 border-b border-slate-100">
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-1">Event</p>
                <h2 class="text-lg font-black text-slate-900">{{ $booking->event->title }}</h2>
                <p class="text-xs font-bold text-slate-500 mt-1">
                    <i class="far fa-calendar-alt mr-1"></i>
                    {{ \Carbon\Carbon::parse($booking->event->event_date)->format('l, M d, Y') }}
                </p>
            </div>

            {{-- Attendee Info --}}
            <div class="mb-6 pb-6 border-b border-slate-100">
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-2">Primary Attendee</p>
                <p class="text-lg font-black text-slate-900">{{ $booking->full_name }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $booking->email }}</p>
            </div>

            {{-- Reference & Group --}}
            <div class="grid grid-cols-2 gap-4 mb-6 pb-6 border-b border-slate-100">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-2">Reference</p>
                    <p class="text-xs font-black font-mono bg-slate-50 p-2 rounded-lg">{{ $booking->reference_no }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-2">Group Size</p>
                    <p class="text-lg font-black text-slate-900">
                        {{ $totalGuests }}
                        <span class="text-xs font-bold text-slate-400">{{ Str::plural('Person', $totalGuests) }}</span>
                    </p>
                    <p class="text-[10px] text-slate-400 mt-1">{{ $booking->adult_count }}A · {{ $booking->child_count }}C · {{ $booking->infant_count }}I</p>
                </div>
            </div>

            {{-- Check-in Audit Trail (show when scanned) --}}
            @if($booking->check_in_at)
            <div class="bg-{{ $alreadyScanned ? 'amber' : 'green' }}-50 border border-{{ $alreadyScanned ? 'amber' : 'green' }}-200 rounded-2xl p-5 mb-6">
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-{{ $alreadyScanned ? 'amber' : 'green' }}-700 mb-3">
                    <i class="fas fa-clock mr-1"></i> Check-In Record
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">Entry Time</p>
                        <p class="text-sm font-black text-slate-900">{{ $booking->check_in_at->format('H:i:s') }}</p>
                        <p class="text-[10px] text-slate-400">{{ $booking->check_in_at->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mb-1">Approved By</p>
                        <p class="text-sm font-black text-slate-900">
                            {{ $booking->checker ? ($booking->checker->username ?? $booking->checker->email) : 'Admin Staff' }}
                        </p>
                        <p class="text-[10px] text-slate-400">Counter Staff</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Action Button --}}
            <a href="{{ route('admin.events.bookings') }}" class="block text-center w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-xl">
                <i class="fas fa-list mr-2"></i> Back to Attendee Log
            </a>
        </div>
    </div>
</div>
@endsection
