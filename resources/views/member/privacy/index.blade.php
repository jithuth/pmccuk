@extends('layouts.app')

@section('title', 'Privacy & Personal Data Center | PMCC-UK')
@section('meta_description', 'Review your personal information, family records, exercise your UK GDPR privacy rights, submit Subject Access Requests, or update consents.')

@section('content')
<div class="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Breadcrumb & Member Header -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <nav class="flex text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2 gap-2 items-center">
                    <a href="{{ route('home') }}" class="hover:text-blue-900">Home</a>
                    <span>/</span>
                    <span class="text-slate-700">My Account</span>
                    <span>/</span>
                    <span class="text-blue-900 font-bold">Privacy & Personal Data</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-blue-900 text-white flex items-center justify-center text-lg">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>
                    Member Privacy Center
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Welcome, <strong class="text-slate-900">{{ $member->full_name }}</strong> (ID: {{ $member->membership_id_assigned ?: 'PENDING' }}). You have full visibility and control over your personal records under the UK General Data Protection Regulation.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <span class="px-3.5 py-1.5 rounded-full text-xs font-extrabold tracking-wide uppercase 
                    {{ $member->isActive() ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' }}">
                    Status: {{ $member->getMembershipStatusLabel() }}
                </span>
                <form action="{{ route('member.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-2">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm flex items-center gap-3 shadow-sm">
                <i class="fa-solid fa-circle-check text-emerald-600 text-xl"></i>
                <div>
                    <strong class="font-bold">Success:</strong> {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm flex items-center gap-3 shadow-sm">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-xl"></i>
                <div>
                    <strong class="font-bold">Notice:</strong> {{ session('error') }}
                </div>
            </div>
        @endif

        <!-- Main Navigation Tabs -->
        @php
            $activeTab = request()->query('tab', 'profile');
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="flex border-b border-slate-200 overflow-x-auto scrollbar-none text-sm font-bold text-slate-600 bg-slate-50/50">
                <a href="?tab=profile" class="px-6 py-4 flex items-center gap-2 border-b-2 whitespace-nowrap transition-colors {{ $activeTab === 'profile' ? 'border-blue-900 text-blue-900 bg-white' : 'border-transparent hover:text-slate-900' }}">
                    <i class="fa-solid fa-id-card"></i> Personal Profile
                </a>
                <a href="?tab=family" class="px-6 py-4 flex items-center gap-2 border-b-2 whitespace-nowrap transition-colors {{ $activeTab === 'family' ? 'border-blue-900 text-blue-900 bg-white' : 'border-transparent hover:text-slate-900' }}">
                    <i class="fa-solid fa-people-roof"></i> Family & Dependents
                </a>
                <a href="?tab=sar" class="px-6 py-4 flex items-center gap-2 border-b-2 whitespace-nowrap transition-colors {{ $activeTab === 'sar' ? 'border-blue-900 text-blue-900 bg-white' : 'border-transparent hover:text-slate-900' }}">
                    <i class="fa-solid fa-download"></i> Download My Data (SAR)
                </a>
                <a href="?tab=rectification" class="px-6 py-4 flex items-center gap-2 border-b-2 whitespace-nowrap transition-colors {{ $activeTab === 'rectification' ? 'border-blue-900 text-blue-900 bg-white' : 'border-transparent hover:text-slate-900' }}">
                    <i class="fa-solid fa-pen-to-square"></i> Correct My Information
                </a>
                <a href="?tab=erasure" class="px-6 py-4 flex items-center gap-2 border-b-2 whitespace-nowrap transition-colors {{ $activeTab === 'erasure' ? 'border-blue-900 text-blue-900 bg-white' : 'border-transparent hover:text-slate-900' }}">
                    <i class="fa-solid fa-user-xmark"></i> Request Data Erasure
                </a>
                <a href="?tab=consents" class="px-6 py-4 flex items-center gap-2 border-b-2 whitespace-nowrap transition-colors {{ $activeTab === 'consents' ? 'border-blue-900 text-blue-900 bg-white' : 'border-transparent hover:text-slate-900' }}">
                    <i class="fa-solid fa-toggle-on"></i> Communication Consents
                </a>
                <a href="?tab=history" class="px-6 py-4 flex items-center gap-2 border-b-2 whitespace-nowrap transition-colors {{ $activeTab === 'history' ? 'border-blue-900 text-blue-900 bg-white' : 'border-transparent hover:text-slate-900' }}">
                    <i class="fa-solid fa-clock-rotate-left"></i> Request History
                    @if($member->privacyRequests->count() > 0)
                        <span class="ml-1 px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-900">{{ $member->privacyRequests->count() }}</span>
                    @endif
                </a>
            </div>

            <!-- TAB 1: PERSONAL INFORMATION -->
            @if($activeTab === 'profile')
                <div class="p-6 sm:p-8 space-y-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Personal Information (Authorized Scope)</h2>
                            <p class="text-xs text-slate-500">Your profile data held under contractual necessity for community membership.</p>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('member.privacy.registration-record') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition flex items-center gap-2">
                                <i class="fa-solid fa-file-contract"></i> View Registration Record
                            </a>
                            <a href="?tab=rectification" class="px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-2">
                                <i class="fa-solid fa-pen"></i> Request Correction
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Profile Card with Photo -->
                        <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 text-center flex flex-col items-center">
                            <img src="{{ $member->photo_url }}" alt="Profile Photo" class="w-32 h-32 rounded-2xl object-cover shadow border border-white mb-4">
                            <h3 class="font-extrabold text-slate-900 text-lg">{{ $member->title }} {{ $member->full_name }}</h3>
                            <p class="text-xs font-mono text-blue-900 font-semibold mt-1">{{ $member->membership_id_assigned ?: 'ID PENDING' }}</p>
                            <span class="mt-3 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-900">
                                {{ strtoupper($member->membership_type ?: 'Standard Member') }}
                            </span>
                        </div>

                        <!-- Data Fields Grid -->
                        <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Email Address</div>
                                <div class="text-sm font-semibold text-slate-900 mt-1">{{ $member->email ?: 'Not recorded' }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Mobile Number</div>
                                <div class="text-sm font-semibold text-slate-900 mt-1">{{ $member->mobile_number ?: 'Not recorded' }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Date of Birth</div>
                                <div class="text-sm font-semibold text-slate-900 mt-1">{{ $member->dob ?: 'Not recorded' }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Marital Status</div>
                                <div class="text-sm font-semibold text-slate-900 mt-1">{{ ucfirst($member->marital_status ?: 'Unspecified') }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 sm:col-span-2">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Residential Address</div>
                                <div class="text-sm font-semibold text-slate-900 mt-1">
                                    {{ $member->house_details ?: 'House details on file' }}, {{ $member->residing_area ?: 'Plymouth' }}, {{ $member->post_code }}
                                </div>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Emergency Contact Name</div>
                                <div class="text-sm font-semibold text-slate-900 mt-1">{{ $member->emergency_name ?: 'None specified' }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Emergency Contact Phone</div>
                                <div class="text-sm font-semibold text-slate-900 mt-1">{{ $member->emergency_mobile ?: 'None specified' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- TAB 2: FAMILY INFORMATION -->
            @if($activeTab === 'family')
                <div class="p-6 sm:p-8 space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Family & Dependent Records</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            In compliance with UK GDPR Article 8 and Information Commissioner's Office guidance, you are authorized to view dependent children for whom you possess parental responsibility. Note: Adult family members retain independent privacy rights.
                        </p>
                    </div>

                    @if($member->spouse_name)
                        <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-700 flex items-center gap-2">
                                <i class="fa-solid fa-heart text-rose-600"></i> Spouse / Partner Record
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                <div><span class="text-slate-400 font-bold block">Spouse Name:</span> <span class="text-slate-900 font-semibold">{{ $member->spouse_name }}</span></div>
                                <div><span class="text-slate-400 font-bold block">Spouse Mobile:</span> <span class="text-slate-900 font-semibold">{{ $member->spouse_mobile ?: 'N/A' }}</span></div>
                                <div><span class="text-slate-400 font-bold block">Spouse DOB:</span> <span class="text-slate-900 font-semibold">{{ $member->spouse_dob ?: 'N/A' }}</span></div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-children text-blue-900"></i> Registered Children
                        </h3>

                        @if($member->children->count() > 0)
                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="w-full text-left text-sm text-slate-700">
                                    <thead class="bg-slate-100 text-xs font-bold uppercase text-slate-600">
                                        <tr>
                                            <th class="py-3 px-4">#</th>
                                            <th class="py-3 px-4">Child Name</th>
                                            <th class="py-3 px-4">Gender</th>
                                            <th class="py-3 px-4">Date of Birth</th>
                                            <th class="py-3 px-4">Age Bracket</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        @foreach($member->children as $i => $c)
                                            <tr class="hover:bg-slate-50">
                                                <td class="py-3 px-4 font-mono text-xs">{{ $i + 1 }}</td>
                                                <td class="py-3 px-4 font-bold text-slate-900">{{ $c->child_name }}</td>
                                                <td class="py-3 px-4">{{ ucfirst($c->child_gender ?: ($c->sex ?: 'N/A')) }}</td>
                                                <td class="py-3 px-4">{{ $c->dob ?: ($c->child_dob ?: 'Not recorded') }}</td>
                                                <td class="py-3 px-4">
                                                    @if($c->age !== null)
                                                        <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $c->age < 18 ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800' }}">
                                                            {{ $c->age < 18 ? 'Minor (Under 18)' : 'Adult (18+)' }}
                                                        </span>
                                                    @else
                                                        <span class="text-slate-400 text-xs">Unspecified</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-6 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300 text-xs text-slate-500">
                                No children or dependents are currently attached to this membership.
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- TAB 3: DOWNLOAD MY DATA (SAR) -->
            @if($activeTab === 'sar')
                <div class="p-6 sm:p-8 space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Subject Access Request (Article 15 UK GDPR)</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            You have the statutory right to receive confirmation as to whether personal data concerning you is being processed, and a copy of the personal data undergoing processing.
                        </p>
                    </div>

                    <div class="p-5 rounded-2xl bg-blue-50 border border-blue-200 text-blue-950 text-xs space-y-2">
                        <div class="font-bold text-sm flex items-center gap-2 text-blue-900">
                            <i class="fa-solid fa-circle-info"></i> How PMCC-UK Fulfills Subject Access Requests:
                        </div>
                        <ul class="list-disc list-inside space-y-1 pl-2 text-slate-700">
                            <li>Requests are fulfilled without charge within <strong>one calendar month</strong> of receipt.</li>
                            <li>Your complete disclosure includes membership profile fields, registered family dependents, original application records, event attendance logs, and accounting transaction histories.</li>
                            <li>Exports are provided in portable, machine-readable <strong>JSON</strong> format and structured <strong>CSV</strong> spreadsheets.</li>
                            <li>Download links are protected by encrypted, time-expiring security tokens.</li>
                        </ul>
                    </div>

                    <form action="{{ route('member.privacy.sar.submit') }}" method="POST" class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
                        @csrf
                        <h3 class="font-bold text-slate-900 text-sm">Submit New Subject Access Request</h3>
                        <div>
                            <label for="sar_notes" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Specific Scope / Notes (Optional)</label>
                            <textarea id="sar_notes" name="notes" rows="3" placeholder="If you require specific documentation or communication logs, describe them here..."
                                      class="mt-1 block w-full p-3 text-slate-900 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-600 focus:border-blue-600"></textarea>
                        </div>

                        <button type="submit" class="py-3 px-6 bg-blue-900 hover:bg-blue-800 text-white font-bold rounded-xl text-xs transition flex items-center gap-2 shadow">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Register Subject Access Request</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- TAB 4: RECTIFICATION -->
            @if($activeTab === 'rectification')
                <div class="p-6 sm:p-8 space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Request Data Rectification (Article 16 UK GDPR)</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            You have the statutory right to obtain without undue delay the rectification of inaccurate personal data concerning you.
                        </p>
                    </div>

                    <form action="{{ route('member.privacy.rectification.submit') }}" method="POST" class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-5">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="field_category" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Data Category</label>
                                <select id="field_category" name="field_category" required class="mt-1 block w-full p-3 text-slate-900 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-600">
                                    <option value="Personal Profile">Personal Profile (Name, Title, DOB)</option>
                                    <option value="Contact Information">Contact Information (Email, Phone)</option>
                                    <option value="Residential Address">Residential Address & Postcode</option>
                                    <option value="Family Information">Spouse / Family Dependents</option>
                                    <option value="Emergency Contact">Emergency Contact</option>
                                </select>
                            </div>
                            <div>
                                <label for="field_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Field Name to Correct</label>
                                <select id="field_name" name="field_name" required class="mt-1 block w-full p-3 text-slate-900 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-600">
                                    <option value="full_name">Full Name</option>
                                    <option value="email">Email Address</option>
                                    <option value="mobile_number">Mobile Number</option>
                                    <option value="dob">Date of Birth</option>
                                    <option value="house_details">House Details / Street</option>
                                    <option value="post_code">Postcode</option>
                                    <option value="residing_area">Residing Area</option>
                                    <option value="spouse_name">Spouse Name</option>
                                    <option value="spouse_mobile">Spouse Mobile</option>
                                    <option value="emergency_name">Emergency Contact Name</option>
                                    <option value="emergency_mobile">Emergency Contact Phone</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="proposed_value" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Proposed Accurate Information</label>
                            <input id="proposed_value" name="proposed_value" type="text" required placeholder="Enter the updated, accurate detail"
                                   class="mt-1 block w-full p-3 text-slate-900 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-600">
                        </div>

                        <div>
                            <label for="justification" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Reason / Supporting Note</label>
                            <textarea id="justification" name="justification" rows="3" required placeholder="Describe why this correction is needed (e.g. moved residence, typo in phone number)..."
                                      class="mt-1 block w-full p-3 text-slate-900 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-600"></textarea>
                        </div>

                        <button type="submit" class="py-3 px-6 bg-blue-900 hover:bg-blue-800 text-white font-bold rounded-xl text-xs transition flex items-center gap-2 shadow">
                            <i class="fa-solid fa-check"></i>
                            <span>Submit Rectification Request</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- TAB 5: ERASURE -->
            @if($activeTab === 'erasure')
                <div class="p-6 sm:p-8 space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Request Data Erasure (Article 17 UK GDPR)</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            You have the right to request the erasure of personal data without undue delay where statutory conditions under UK GDPR Article 17 apply.
                        </p>
                    </div>

                    <div class="p-5 rounded-2xl bg-amber-50 border border-amber-300 text-amber-950 text-xs space-y-2">
                        <div class="font-extrabold text-sm flex items-center gap-2 text-amber-900">
                            <i class="fa-solid fa-triangle-exclamation"></i> Critical Governance Notice on Membership Continuation:
                        </div>
                        <p class="font-bold text-amber-900">
                            Data erasure must NEVER automatically cancel your club membership.
                        </p>
                        <p class="text-slate-700">
                            Under PMCC-UK governance, you may exercise your Right to Erasure regarding optional personal data, past event attendance logs, or promotional photos without forfeiting your membership standing or right to participate in community governance.
                        </p>
                        <p class="text-slate-700">
                            <strong>Statutory Legal Exception:</strong> Where PMCC-UK is bound by statutory legal obligations (such as the UK Taxes Management Act 1970 requiring minimum 6-year retention of financial accounting records), those specific financial transaction records are legally exempt from erasure under Article 17(3)(b) UK GDPR.
                        </p>
                    </div>

                    <form action="{{ route('member.privacy.erasure.submit') }}" method="POST" class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-5">
                        @csrf
                        <div>
                            <label for="erasure_scope" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Requested Scope of Erasure</label>
                            <select id="erasure_scope" name="erasure_scope" required class="mt-1 block w-full p-3 text-slate-900 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-600">
                                <option value="specific_fields">Discretionary Profile Fields (Secondary phone, photos, non-mandatory details)</option>
                                <option value="past_events">Past Event Attendance & Booking History</option>
                                <option value="all_discretionary">All Non-Statutory Information (Retain only mandatory membership identity & legal financial ledger)</option>
                            </select>
                        </div>

                        <div>
                            <label for="erasure_reason" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Reason for Request</label>
                            <textarea id="erasure_reason" name="erasure_reason" rows="3" required placeholder="Please state your reasons or specific records requested for disposal..."
                                      class="mt-1 block w-full p-3 text-slate-900 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-600"></textarea>
                        </div>

                        <div class="p-4 bg-white rounded-xl border border-slate-200">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="confirm_non_cancel" required class="mt-0.5 rounded border-slate-300 text-blue-900 focus:ring-blue-600">
                                <span class="text-xs text-slate-700 font-medium">
                                    I acknowledge that submitting this erasure request applies strictly to my personal data under the lawful scope identified, and does not cancel my community membership.
                                </span>
                            </label>
                        </div>

                        <button type="submit" class="py-3 px-6 bg-rose-800 hover:bg-rose-900 text-white font-bold rounded-xl text-xs transition flex items-center gap-2 shadow">
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Submit Formal Erasure Request</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- TAB 6: COMMUNICATION CONSENTS -->
            @if($activeTab === 'consents')
                <div class="p-6 sm:p-8 space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Communication & Privacy Consents</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Essential membership administration relies on contractual necessity. Below, you may configure optional, consent-based communication channels.
                        </p>
                    </div>

                    @php
                        $consentsMap = $member->consents->pluck('granted', 'consent_key');
                    @endphp

                    <form action="{{ route('member.privacy.consents.update') }}" method="POST" class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-6">
                        @csrf
                        <div class="space-y-4">
                            <div class="flex items-start justify-between p-4 bg-white rounded-xl border border-slate-200 gap-4">
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">Community WhatsApp Broadcasts</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Receive announcements, emergency club notices, and cultural program alerts via WhatsApp.</p>
                                </div>
                                <input type="checkbox" name="whatsapp_broadcasts" value="1" {{ !empty($consentsMap['whatsapp_broadcasts']) ? 'checked' : '' }}
                                       class="rounded border-slate-300 text-blue-900 focus:ring-blue-600 h-5 w-5">
                            </div>

                            <div class="flex items-start justify-between p-4 bg-white rounded-xl border border-slate-200 gap-4">
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">Optional Promotional & Sponsor Emails</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Receive cultural festival discount offers and news from verified community business partners.</p>
                                </div>
                                <input type="checkbox" name="marketing_emails" value="1" {{ !empty($consentsMap['marketing_emails']) ? 'checked' : '' }}
                                       class="rounded border-slate-300 text-blue-900 focus:ring-blue-600 h-5 w-5">
                            </div>

                            <div class="flex items-start justify-between p-4 bg-white rounded-xl border border-slate-200 gap-4">
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">Public Event Photo Publication</h4>
                                    <p class="text-xs text-slate-500 mt-0.5">Consent to group festival photographs being featured on the public PMCC-UK gallery and social channels.</p>
                                </div>
                                <input type="checkbox" name="photo_publication" value="1" {{ !empty($consentsMap['photo_publication']) ? 'checked' : '' }}
                                       class="rounded border-slate-300 text-blue-900 focus:ring-blue-600 h-5 w-5">
                            </div>
                        </div>

                        <button type="submit" class="py-3 px-6 bg-blue-900 hover:bg-blue-800 text-white font-bold rounded-xl text-xs transition flex items-center gap-2 shadow">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Consent Preferences</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- TAB 7: PRIVACY REQUEST HISTORY -->
            @if($activeTab === 'history')
                <div class="p-6 sm:p-8 space-y-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Privacy Request History & Audit Tracker</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Real-time status tracking for all statutory privacy requests submitted by you.
                        </p>
                    </div>

                    @if($member->privacyRequests->count() > 0)
                        <div class="space-y-4">
                            @foreach($member->privacyRequests as $req)
                                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-3">
                                        <div class="flex items-center gap-3">
                                            <span class="font-mono text-xs font-extrabold text-blue-900 bg-blue-100 px-3 py-1 rounded-lg">
                                                {{ $req->reference_number }}
                                            </span>
                                            <span class="text-xs font-bold uppercase text-slate-600">
                                                Type: {{ strtoupper($req->request_type) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase bg-{{ $req->getStatusBadgeClass() }}-100 text-{{ $req->getStatusBadgeClass() }}-800">
                                                Status: {{ str_replace('_', ' ', $req->status) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs text-slate-600">
                                        <div><span class="text-slate-400 font-bold">Submitted:</span> {{ $req->received_at->format('d M Y, h:i A') }}</div>
                                        <div>
                                            <span class="text-slate-400 font-bold">Statutory Response Due:</span> 
                                            <strong class="{{ $req->isOverdue() ? 'text-rose-600' : 'text-slate-900' }}">
                                                {{ $req->getEffectiveDeadline()->format('d M Y') }}
                                            </strong>
                                        </div>
                                        <div><span class="text-slate-400 font-bold">Identity Verification:</span> {{ $req->identity_verified ? 'Verified' : 'Pending Review' }}</div>
                                    </div>

                                    @if($req->decision_reasoning)
                                        <div class="p-3 bg-white rounded-xl border border-slate-200 text-xs">
                                            <span class="font-bold text-slate-800">Decision Outcome & Notes:</span>
                                            <p class="text-slate-600 mt-0.5">{{ $req->decision_reasoning }}</p>
                                        </div>
                                    @endif

                                    <!-- Secure Downloads for SAR -->
                                    @if($req->documents->count() > 0)
                                        <div class="pt-2 border-t border-slate-200">
                                            <span class="text-xs font-bold text-slate-700 block mb-2">Secure Download Package (Expiring Link):</span>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($req->documents as $doc)
                                                    @if($doc->isDownloadValid())
                                                        <a href="{{ route('member.privacy.download', $doc->download_token) }}"
                                                           class="px-3.5 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-bold transition flex items-center gap-2">
                                                            <i class="fa-solid fa-download"></i>
                                                            <span>Download {{ $doc->file_type === 'export_json' ? 'JSON' : 'CSV' }}</span>
                                                            <span class="text-[10px] opacity-75">({{ round($doc->file_size / 1024, 1) }} KB)</span>
                                                        </a>
                                                    @else
                                                        <span class="px-3 py-1 bg-slate-200 text-slate-500 rounded-lg text-xs font-medium">
                                                            {{ $doc->file_name }} (Link Expired)
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300 text-xs text-slate-500">
                            You have not submitted any statutory privacy requests.
                        </div>
                    @endif
                </div>
            @endif

        </div>

    </div>
</div>
@endsection
