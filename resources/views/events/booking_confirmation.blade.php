@extends('layouts.app')

@section('title', 'Booking Submitted')

@push('styles')
<style>
    @media print {
        header, footer, .nav-btn, .no-print {
            display: none !important;
        }
        body {
            background: white !important;
            padding: 0 !important;
        }
        .printable-card {
            box-shadow: none !important;
            border: 2px solid #f1f5f9 !important;
            border-radius: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }
        .main-gradient-bg {
            background: none !important;
        }
        .bank-details-box {
            background: #fff !important;
            color: #000 !important;
            border: 1px solid #000 !important;
        }
        .highlight-yellow {
            background-color: #fef08a !important; /* Yellow-200 */
            color: #000 !important;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 800;
        }
    }
    .highlight-yellow {
        background-color: #fef08a; /* Tailwind Yellow-200 */
        color: #1e293b;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 800;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-[#f8fafc] py-24 main-gradient-bg">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <div class="mb-12 no-print">
            <div class="w-20 h-20 bg-green-500 text-white rounded-full flex items-center justify-center text-3xl mx-auto mb-6 shadow-2xl shadow-green-100">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="text-4xl font-black text-slate-900 mb-4 tracking-tight uppercase italic">Booking <span class="text-green-500">Confirmed</span></h1>
            <p class="text-slate-500 font-medium">Your request for <span class="text-slate-900 font-bold uppercase">{{ $event->title }}</span> has been logged.</p>
        </div>

        <div class="bg-white rounded-[3rem] shadow-2xl p-12 text-left border border-slate-100 relative overflow-hidden printable-card">
            <!-- Header for Print -->
            <div class="hidden print:flex justify-between items-center border-b-2 border-slate-100 pb-8 mb-10">
                <div class="text-left">
                    <h2 class="text-2xl font-black text-slate-900 uppercase italic">PMCC <span class="text-primary italic">UK</span></h2>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Official Event Booking Voucher</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-300">Reference No</p>
                    <p class="text-lg font-black text-primary">{{ $reference }}</p>
                </div>
            </div>

            <div class="absolute top-0 right-0 p-8 no-print">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-300">Reference No</span>
                <p class="text-sm font-black text-primary">{{ $reference }}</p>
            </div>

            <div class="mb-12">
                <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-400 mb-8 pb-4 border-b border-slate-50">Booking Summary</h3>
                <div class="space-y-4">
                    @foreach($booking->attendee_breakdown as $item)
                        <div class="flex justify-between items-center bg-slate-50 p-6 rounded-2xl border border-slate-100">
                            <div>
                                <p class="font-black text-slate-900 uppercase text-xs tracking-wider">{{ $item['category'] }}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">Quantity: {{ $item['count'] }}</p>
                            </div>
                            <p class="font-black text-slate-900">£{{ number_format($item['subtotal'], 2) }}</p>
                        </div>
                    @endforeach
                    <div class="flex justify-between items-center pt-8 border-t-2 border-dashed border-slate-100 mt-8 px-4">
                        <p class="text-xs font-black text-slate-400 uppercase tracking-[0.3em]">Total Balance Due</p>
                        <p class="text-4xl font-black text-primary tracking-tighter">£{{ number_format($booking->total_amount, 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 rounded-[2.5rem] p-10 text-white bank-details-box relative overflow-hidden">
                <div class="absolute top-0 right-0 opacity-5 -mr-10 -mt-10">
                    <i class="fas fa-university text-[12rem]"></i>
                </div>

                <h4 class="text-primary text-xs font-black uppercase tracking-[0.4em] mb-6 flex items-center">
                    <span class="w-8 h-0.5 bg-primary mr-4"></span>
                    Transfer Instructions
                </h4>
                <p class="text-slate-400 text-xs mb-10 leading-relaxed uppercase tracking-widest font-bold">Please complete your transfer to finalize your reservation. Important details are highlighted below.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-10 relative z-10">
                    <div>
                        <p class="text-[10px] uppercase font-black tracking-[0.2em] text-slate-500 mb-2">Account Name</p>
                        <p class="font-bold text-sm uppercase">{{ $settings['bank_account_name'] ?? 'PMCC-UK' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-black tracking-[0.2em] text-slate-500 mb-2">Bank Name</p>
                        <p class="font-bold text-sm uppercase">{{ $settings['bank_name'] ?? 'Barclays Bank' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-black tracking-[0.2em] text-slate-500 mb-2">Sort Code</p>
                        <p class="inline-block highlight-yellow text-xl tracking-[0.1em]">{{ $settings['bank_sort_code'] ?? 'XX-XX-XX' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-black tracking-[0.2em] text-slate-500 mb-2">Account Number</p>
                        <p class="inline-block highlight-yellow text-xl tracking-[0.1em]">{{ $settings['bank_account_no'] ?? 'XXXXXXXX' }}</p>
                    </div>
                </div>

                <div class="bg-white/5 p-6 rounded-2xl border border-white/10 text-center print:border-slate-900">
                    <p class="text-[10px] font-black uppercase tracking-[0.4em] text-primary mb-2">Required Reference Code</p>
                    <p class="text-4xl font-black tracking-tighter italic highlight-yellow print:bg-none">{{ $reference }}</p>
                </div>
            </div>
        </div>

        <div class="mt-12 flex justify-center gap-6 no-print">
            <a href="{{ route('home') }}" class="flex-1 bg-white text-slate-900 px-8 py-5 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-xl hover:bg-slate-50 transition-all border border-slate-100 flex items-center justify-center">
                <i class="fas fa-home mr-3 text-secondary"></i> Home
            </a>
            <button onclick="window.print()" class="flex-1 bg-primary text-white px-8 py-5 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-2xl shadow-primary/20 hover:bg-slate-900 transition-all flex items-center justify-center">
                <i class="fas fa-print mr-3"></i> Print Voucher
            </button>
        </div>
    </div>
</div>
@endsection
