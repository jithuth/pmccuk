@extends('layouts.app')

@section('title', 'Membership Registration Record | PMCC-UK')

@section('content')
<div class="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('member.privacy.index') }}" class="text-xs font-bold text-blue-900 hover:underline flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back to Privacy Center</span>
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow">
                <i class="fa-solid fa-print"></i>
                <span>Print Official Record</span>
            </button>
        </div>

        <div class="bg-white p-8 sm:p-10 rounded-2xl shadow-sm border border-slate-200 space-y-8" id="printableRecord">
            <!-- Header -->
            <div class="border-b-2 border-slate-900 pb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">PMCC-UK OFFICIAL MEMBERSHIP RECORD</h1>
                    <p class="text-xs text-slate-500 font-semibold mt-1">Plymouth Malayalee Community Club • Voluntary Cultural Association</p>
                </div>
                <div class="text-right">
                    <span class="font-mono text-sm font-extrabold text-blue-900 bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-200 inline-block">
                        {{ $member->membership_id_assigned ?: 'PMCC-PENDING' }}
                    </span>
                    <span class="block text-[10px] text-slate-400 mt-1 uppercase font-bold">Registration Reference</span>
                </div>
            </div>

            <!-- Member Summary -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                <div class="space-y-3">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Full Name:</span>
                        <span class="font-extrabold text-slate-900 text-base">{{ $member->title }} {{ $member->full_name }}</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Membership Category:</span>
                        <span class="font-bold text-blue-900 uppercase">{{ $member->membership_type ?: 'Standard' }}</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Membership Status:</span>
                        <span class="font-bold text-slate-900">{{ $member->getMembershipStatusLabel() }}</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Validity Expiry:</span>
                        <span class="font-semibold text-slate-900">{{ $member->expiry_date ?: 'Permanent / Ongoing' }}</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Registered Email:</span>
                        <span class="font-semibold text-slate-900">{{ $member->email }}</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Mobile Contact:</span>
                        <span class="font-semibold text-slate-900">{{ $member->mobile_number }}</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Registered Address:</span>
                        <span class="font-semibold text-slate-900">{{ $member->house_details }}, {{ $member->residing_area }}, {{ $member->post_code }}</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Registration Timestamp:</span>
                        <span class="font-semibold text-slate-900">{{ $member->created_at ? $member->created_at->format('d M Y, h:i A') : 'Archived record' }}</span>
                    </div>
                </div>
            </div>

            <!-- Spouse & Dependents Section -->
            @if($member->spouse_name || $member->children->count() > 0)
                <div class="pt-6 border-t border-slate-200 space-y-4">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700">Family Members Registered Under Household:</h3>
                    
                    @if($member->spouse_name)
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs flex justify-between items-center">
                            <div>
                                <span class="text-slate-400 font-bold block">Spouse:</span>
                                <span class="font-bold text-slate-900 text-sm">{{ $member->spouse_name }}</span>
                            </div>
                            <span class="text-slate-500 font-mono">Mobile: {{ $member->spouse_mobile ?: 'N/A' }}</span>
                        </div>
                    @endif

                    @if($member->children->count() > 0)
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-100 font-bold uppercase text-slate-600">
                                    <tr>
                                        <th class="py-2.5 px-3">Dependent Name</th>
                                        <th class="py-2.5 px-3">Gender</th>
                                        <th class="py-2.5 px-3">Date of Birth</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($member->children as $c)
                                        <tr>
                                            <td class="py-2 px-3 font-bold text-slate-900">{{ $c->child_name }}</td>
                                            <td class="py-2 px-3">{{ ucfirst($c->child_gender ?: ($c->sex ?: 'N/A')) }}</td>
                                            <td class="py-2 px-3">{{ $c->dob ?: ($c->child_dob ?: 'N/A') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Verification Footer -->
            <div class="pt-6 border-t border-slate-200 text-center text-xs text-slate-400 space-y-1">
                <p>This document is an authentic summary of membership records maintained by PMCC-UK under UK GDPR compliance standards.</p>
                <p>System Hash: {{ hash_hmac('sha256', (string) $member->id . $member->created_at, config('app.key')) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
