@extends('layouts.app')

@section('title', 'Exclusive Member Offers | ' . ($settings['site_name'] ?? 'PMCC-UK'))

@section('content')
<!-- Header -->
<section class="py-20 bg-emerald-600 text-white text-center relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 relative z-10">
        <h1 class="text-5xl font-black uppercase tracking-tighter mb-4">Sponsor Offers</h1>
        <p class="text-xl opacity-90 max-w-2xl mx-auto font-medium">Unlock exclusive discounts and benefits from our community partners.</p>
    </div>
</section>

<!-- Offers Grid -->
<section class="py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid lg:grid-cols-2 gap-10">
            @forelse($offers as $o)
            <div class="bg-white rounded-[2rem] overflow-hidden shadow-xl shadow-slate-200/50 flex flex-col md:flex-row border border-slate-100 group">
                <div class="md:w-48 bg-white p-8 flex items-center justify-center border-b md:border-b-0 md:border-r border-slate-100">
                    <img src="{{ $o->logo_url ? (str_starts_with($o->logo_url, 'http') ? $o->logo_url : asset('storage/'.$o->logo_url)) : 'https://placehold.co/200x200?text='.$o->sponsor_name }}" 
                         class="max-w-full max-h-32 object-contain group-hover:scale-110 transition-transform duration-500">
                </div>
                <div class="p-10 flex-1 relative">
                    <div class="absolute top-0 right-0 bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase px-4 py-1.5 rounded-bl-2xl">
                        Verified Partner
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-3">{{ $o->title }}</h3>
                    <p class="text-slate-500 mb-8 leading-relaxed">{{ $o->description }}</p>
                    
                    <div class="flex items-center justify-between mt-auto pt-6 border-t border-slate-50">
                        <div>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1">Coupon Code</p>
                            <p class="text-xl font-mono font-bold text-primary">{{ $o->promo_code ?? 'SHOW ID CARD' }}</p>
                        </div>
                        <a href="{{ $o->website_url ?? '#' }}" target="_blank" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-bold text-xs uppercase hover:bg-emerald-600 transition shadow-lg">Redeem Offer</a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-2 text-center py-20">
                <i class="fas fa-tags fa-4x text-slate-200 mb-6"></i>
                <h2 class="text-2xl font-bold text-slate-400">No active offers available at the moment.</h2>
            </div>
            @endforelse
        </div>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <div class="bg-primary rounded-[3rem] p-16 text-white shadow-2xl relative overflow-hidden">
            <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/10 rounded-full"></div>
            <h2 class="text-3xl font-black mb-6">Become a Community Sponsor</h2>
            <p class="text-lg opacity-80 mb-10">Promote your business to hundreds of local Malayalee families and students in Plymouth.</p>
            <a href="{{ url('contact') }}" class="inline-block bg-white text-primary px-10 py-4 rounded-full font-black uppercase tracking-widest hover:bg-secondary hover:text-white transition shadow-xl">Contact Partnership Team</a>
        </div>
    </div>
</section>
@endsection
