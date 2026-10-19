@extends('layouts.app')

@section('title', 'Security Settings & Active Sessions | PMCC-UK')
@section('meta_description', 'Manage Two-Factor Authentication (MFA), active devices, sessions, and security audit history for your PMCC account.')

@section('content')
<div class="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-8">
        
        <!-- Header -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <nav class="flex text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2 gap-2 items-center">
                    <a href="{{ route('home') }}" class="hover:text-blue-900">Home</a>
                    <span>/</span>
                    <a href="{{ route('member.privacy.index') }}" class="hover:text-blue-900">Privacy Center</a>
                    <span>/</span>
                    <span class="text-blue-900 font-bold">Security & Devices</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-blue-900 text-white flex items-center justify-center text-lg shadow-sm">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    Member Security Centre
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Comprehensive authentication safeguards, Multi-Factor Authentication (MFA), active sessions, and recognized devices for <strong class="text-slate-900">{{ $member->full_name }}</strong>.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('member.privacy.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Privacy Portal</span>
                </a>
                <button type="button" onclick="openReportModal()" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-xl transition flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Report Suspicious Activity</span>
                </button>
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

        <!-- Grid Layout: MFA Card & Quick Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left 2 Cols: Two-Factor Authentication Status -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 space-y-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl {{ $member->two_factor_enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }} flex items-center justify-center text-xl">
                            <i class="fa-solid {{ $member->two_factor_enabled ? 'fa-shield-check' : 'fa-shield-exclamation' }}"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Two-Factor Authentication (TOTP)</h2>
                            <p class="text-xs text-slate-500">Protect your membership profile with an Authenticator application.</p>
                        </div>
                    </div>
                    <div>
                        @if($member->two_factor_enabled)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <i class="fa-solid fa-circle text-[8px] text-emerald-500"></i> Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800 border border-amber-300">
                                <i class="fa-solid fa-circle text-[8px] text-amber-500"></i> Optional (Recommended)
                            </span>
                        @endif
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-2 leading-relaxed">
                    <p>
                        Two-Factor Authentication (MFA) adds an essential extra layer of defense beyond your single-use login OTP. When enabled, signing in requires a dynamic 6-digit verification code from apps such as <strong>Google Authenticator</strong>, <strong>Microsoft Authenticator</strong>, or <strong>1Password</strong>.
                    </p>
                    @if($member->two_factor_enabled)
                        <div class="flex items-center gap-2 text-emerald-800 font-semibold pt-1">
                            <i class="fa-solid fa-check-double text-emerald-600"></i>
                            <span>Enabled on {{ $member->two_factor_confirmed_at?->format('d M Y, H:i') ?? 'Recently' }}. Remaining Single-Use Recovery Codes: <strong>{{ $recoveryCodesCount }}</strong>.</span>
                        </div>
                    @endif
                </div>

                <div class="pt-2 flex flex-wrap items-center gap-3">
                    @if(!$member->two_factor_enabled)
                        <button type="button" onclick="startMfaEnrollment()" class="px-5 py-3 bg-blue-900 hover:bg-blue-800 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center gap-2">
                            <i class="fa-solid fa-qrcode"></i>
                            <span>Setup Two-Factor Authentication</span>
                        </button>
                    @else
                        <button type="button" onclick="openRegenCodesModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-xl text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-key"></i>
                            <span>Regenerate Recovery Codes</span>
                        </button>
                        <button type="button" onclick="openDisableMfaModal()" class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold rounded-xl text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-power-off"></i>
                            <span>Disable MFA</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Right Col: Security Health Summary -->
            <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Account Security Score</h3>
                <div class="flex items-center gap-4">
                    <div class="text-4xl font-extrabold {{ $member->two_factor_enabled ? 'text-emerald-600' : 'text-blue-900' }}">
                        {{ $member->two_factor_enabled ? '95%' : '75%' }}
                    </div>
                    <div class="text-xs text-slate-600">
                        <strong class="block text-slate-900 font-bold">{{ $member->two_factor_enabled ? 'Optimal Security' : 'Good (MFA Recommended)' }}</strong>
                        Password-free login active.
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 space-y-2 text-xs text-slate-600">
                    <div class="flex items-center justify-between">
                        <span>Primary Login Method</span>
                        <strong class="text-slate-900">Secure OTP (Email & WhatsApp)</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Recognized Devices</span>
                        <strong class="text-slate-900">{{ $devices->count() }} Device(s)</strong>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Active Concurrent Sessions</span>
                        <strong class="text-slate-900">{{ count($sessions) }} Session(s)</strong>
                    </div>
                </div>
            </div>

        </div>

        <!-- Section: Active Sessions & Device Management -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-laptop text-blue-900"></i>
                        Active Sessions & Recognized Devices
                    </h2>
                    <p class="text-xs text-slate-500">Review sessions currently logged into your PMCC profile. You can revoke any unfamiliar session at any time.</p>
                </div>
                <div>
                    <form action="{{ route('member.security.sessions.revoke-others') }}" method="POST" onsubmit="return confirm('Terminate all other active sessions except your current device?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-2">
                            <i class="fa-solid fa-ban text-rose-600"></i>
                            <span>Revoke All Other Sessions</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sessions Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider bg-slate-50">
                            <th class="py-3 px-4">Device & Browser</th>
                            <th class="py-3 px-4">Location</th>
                            <th class="py-3 px-4">IP Address</th>
                            <th class="py-3 px-4">Last Activity</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($sessions as $s)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 font-medium flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 text-sm">
                                        @if(str_contains(strtolower($s['device_name']), 'phone') || str_contains(strtolower($s['device_name']), 'android') || str_contains(strtolower($s['device_name']), 'ios'))
                                            <i class="fa-solid fa-mobile-screen"></i>
                                        @else
                                            <i class="fa-solid fa-desktop"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $s['device_name'] }}</div>
                                        @if($s['is_current'])
                                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-100 text-blue-800">
                                                This Current Session
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">{{ $s['location'] }}</td>
                                <td class="py-3.5 px-4 font-mono text-slate-500">{{ $s['ip_address'] }}</td>
                                <td class="py-3.5 px-4">
                                    {{ \Carbon\Carbon::parse($s['last_activity'])->diffForHumans() }}
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    @if(!$s['is_current'] && $s['is_active'])
                                        <form action="{{ route('member.security.sessions.revoke') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="session_id" value="{{ $s['id'] }}">
                                            <button type="submit" class="px-2.5 py-1 text-xs font-bold text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg transition">
                                                Revoke
                                            </button>
                                        </form>
                                    @elseif(!$s['is_active'])
                                        <span class="text-slate-400 italic">Terminated</span>
                                    @else
                                        <span class="text-emerald-700 font-bold">Active Now</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">No session records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section: Security Audit History -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 space-y-6">
            <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-blue-900"></i>
                Recent Account Security Audit Trail
            </h2>
            <p class="text-xs text-slate-500">Transparent timeline of authentication challenges, session events, and device recognitions.</p>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider bg-slate-50">
                            <th class="py-3 px-4">Event</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">IP & Location</th>
                            <th class="py-3 px-4">Timestamp (UTC)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($recentEvents as $e)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ ucwords(str_replace('_', ' ', $e->action)) }}
                                    @if($e->reason_code)
                                        <span class="block text-[11px] font-normal text-slate-400">Code: {{ $e->reason_code }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase 
                                        {{ $e->status === 'success' ? 'bg-emerald-100 text-emerald-800' : ($e->status === 'blocked' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $e->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-mono">{{ $e->ip_address }}</span>
                                    @if($e->location_summary)
                                        <span class="block text-slate-400 text-[11px]">{{ $e->location_summary }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-slate-500 font-mono">
                                    {{ $e->created_at->format('d M Y H:i:s') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">No security audit events recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Modal: MFA Setup Wizard -->
<div id="mfaSetupModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-6">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-qrcode text-blue-900"></i>
                Setup Two-Factor Authentication
            </h3>
            <button type="button" onclick="closeMfaModal()" class="text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Wizard Step 1: Scan QR Code -->
        <div id="mfaStep1" class="space-y-4">
            <p class="text-xs text-slate-600">
                1. Scan this QR code using your Authenticator app (e.g. Google Authenticator, Microsoft Authenticator):
            </p>
            <div class="text-center p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                <img id="mfaQrImg" src="" alt="MFA QR Code" class="mx-auto w-48 h-48 border border-white shadow-sm rounded-xl">
                <div class="mt-3 text-xs text-slate-500">
                    <span>Manual Setup Key:</span>
                    <code id="mfaSecretKey" class="block mt-1 font-mono font-bold text-slate-800 bg-white p-2 rounded border border-slate-200 select-all tracking-wider text-sm"></code>
                </div>
            </div>

            <div class="pt-2">
                <label for="mfa_confirm_code" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                    2. Enter 6-digit confirmation code from your app:
                </label>
                <input id="mfa_confirm_code" type="text" maxlength="6" inputmode="numeric" placeholder="123456"
                       class="mt-1 block w-full text-center text-2xl font-bold tracking-widest py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600">
            </div>

            <div id="mfaModalError" class="hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs"></div>

            <button type="button" id="confirmMfaBtn" onclick="confirmMfaEnrollment()" class="w-full py-3.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-md">
                <span>Verify & Activate MFA</span>
                <i class="fa-solid fa-check"></i>
            </button>
        </div>

        <!-- Wizard Step 2: Recovery Codes Presentation (Shown ONLY ONCE) -->
        <div id="mfaStep2" class="hidden space-y-4">
            <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 text-xs flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-lg text-emerald-600"></i>
                <span class="font-bold">MFA Enabled Successfully! Save Your Recovery Codes.</span>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                If you ever lose access to your authenticator app, these <strong>single-use recovery codes</strong> are the only way to sign in. Store them in a safe password manager or print them now.
            </p>

            <div id="recoveryCodesGrid" class="grid grid-cols-2 gap-2 bg-slate-100 p-4 rounded-xl font-mono text-sm font-bold text-slate-900 text-center select-all">
                <!-- Dynamically populated -->
            </div>

            <button type="button" onclick="window.location.reload()" class="w-full py-3 bg-blue-900 hover:bg-blue-800 text-white font-bold rounded-xl text-xs transition">
                I Have Saved My Recovery Codes — Done
            </button>
        </div>

    </div>
</div>

<!-- Modal: Disable MFA Confirmation -->
<div id="disableMfaModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-5">
        <div class="text-center">
            <div class="w-12 h-12 bg-rose-100 text-rose-700 rounded-xl flex items-center justify-center mx-auto text-xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="mt-3 text-lg font-bold text-slate-900">Disable Two-Factor Authentication?</h3>
            <p class="mt-1 text-xs text-slate-600">Enter a code from your authenticator app to confirm deactivation.</p>
        </div>

        <form action="{{ route('member.security.mfa.disable') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <input name="code" type="text" maxlength="8" required placeholder="6-digit authenticator code"
                       class="block w-full text-center text-lg font-bold py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-rose-600">
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="closeDisableMfaModal()" class="w-1/2 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition">
                    Cancel
                </button>
                <button type="submit" class="w-1/2 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition shadow-sm">
                    Confirm Disable
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Report Suspicious Activity -->
<div id="reportModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                Report Suspicious Activity
            </h3>
            <button type="button" onclick="closeReportModal()" class="text-slate-400 hover:text-slate-700">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <p class="text-xs text-slate-600">
            Did you observe an unrecognized device, unexpected verification code, or suspicious session? Submitting this report alerts our community security officers and immediately flags your account for review.
        </p>

        <form action="{{ route('member.security.report') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="report_desc" class="block text-xs font-bold uppercase text-slate-700 mb-1">Incident Details</label>
                <textarea id="report_desc" name="description" rows="4" required minlength="10" maxlength="1000"
                          placeholder="Describe what occurred (e.g. received unexpected OTP on WhatsApp, saw login from unfamiliar IP or device)..."
                          class="block w-full p-3 border border-slate-300 rounded-xl text-xs text-slate-900 focus:ring-2 focus:ring-blue-600"></textarea>
            </div>
            <button type="submit" class="w-full py-3 bg-rose-700 hover:bg-rose-800 text-white font-bold rounded-xl text-xs transition shadow-md flex items-center justify-center gap-2">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Submit Security Report</span>
            </button>
        </form>
    </div>
</div>

<script>
function startMfaEnrollment() {
    const modal = document.getElementById('mfaSetupModal');
    const step1 = document.getElementById('mfaStep1');
    const step2 = document.getElementById('mfaStep2');
    const errBox = document.getElementById('mfaModalError');
    errBox.classList.add('hidden');
    step1.classList.remove('hidden');
    step2.classList.add('hidden');
    modal.classList.remove('hidden');

    fetch("{{ route('member.security.mfa.initiate') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('mfaQrImg').src = data.qr_image_url;
            document.getElementById('mfaSecretKey').innerText = data.secret;
        } else {
            alert('Failed to generate MFA setup: ' + (data.message || 'Unknown error'));
            modal.classList.add('hidden');
        }
    })
    .catch(err => {
        alert('Network error initiating MFA setup.');
        modal.classList.add('hidden');
    });
}

function confirmMfaEnrollment() {
    const code = document.getElementById('mfa_confirm_code').value.trim();
    const errBox = document.getElementById('mfaModalError');
    errBox.classList.add('hidden');

    if (!code || code.length < 6) {
        errBox.innerText = 'Please enter the 6-digit code generated by your authenticator app.';
        errBox.classList.remove('hidden');
        return;
    }

    const btn = document.getElementById('confirmMfaBtn');
    btn.disabled = true;
    btn.innerText = 'Verifying...';

    fetch("{{ route('member.security.mfa.confirm') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ code: code })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<span>Verify & Activate MFA</span> <i class="fa-solid fa-check"></i>';
        if (data.success) {
            document.getElementById('mfaStep1').classList.add('hidden');
            const step2 = document.getElementById('mfaStep2');
            step2.classList.remove('hidden');

            const grid = document.getElementById('recoveryCodesGrid');
            grid.innerHTML = '';
            (data.recovery_codes || []).forEach(c => {
                const el = document.createElement('div');
                el.className = 'p-2 bg-white rounded border border-slate-300 shadow-sm';
                el.innerText = c;
                grid.appendChild(el);
            });
        } else {
            errBox.innerText = data.message || 'Verification failed.';
            errBox.classList.remove('hidden');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<span>Verify & Activate MFA</span> <i class="fa-solid fa-check"></i>';
        errBox.innerText = 'Network error verifying code.';
        errBox.classList.remove('hidden');
    });
}

function closeMfaModal() {
    document.getElementById('mfaSetupModal').classList.add('hidden');
}

function openDisableMfaModal() {
    document.getElementById('disableMfaModal').classList.remove('hidden');
}
function closeDisableMfaModal() {
    document.getElementById('disableMfaModal').classList.add('hidden');
}

function openReportModal() {
    document.getElementById('reportModal').classList.remove('hidden');
}
function closeReportModal() {
    document.getElementById('reportModal').classList.add('hidden');
}
</script>
@endsection
