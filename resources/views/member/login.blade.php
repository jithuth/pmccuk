@extends('layouts.app')

@section('title', 'Member Privacy Center Login | PMCC-UK')
@section('meta_description', 'Secure OTP-based sign-in portal for PMCC-UK members to manage personal data, subject access requests, and privacy rights.')

@section('content')
<div class="min-h-screen py-16 bg-slate-50 flex items-center justify-center px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl shadow-xl border border-slate-200">
        <div class="text-center">
            <div class="w-16 h-16 bg-blue-900 text-white rounded-2xl flex items-center justify-center mx-auto text-2xl shadow-md">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h2 class="mt-4 text-2xl font-extrabold text-slate-900">
                Member Privacy Center
            </h2>
            <p class="mt-1 text-sm text-slate-600">
                UK GDPR Self-Service Portal for Community Members
            </p>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div id="otpStatusAlert" class="hidden p-4 rounded-xl text-sm flex items-start gap-3">
            <i id="otpStatusIcon" class="text-lg mt-0.5"></i>
            <span id="otpStatusMessage"></span>
        </div>

        <!-- Single Step 1: Identifier Entry -->
        <div id="otpStep1" class="space-y-4">
            <div>
                <label for="otp_identifier" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                    Registered Email Address
                </label>
                <div class="mt-1.5 relative rounded-xl shadow-sm">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input id="otp_identifier" type="email" autofocus
                           placeholder="name@example.com"
                           onkeydown="if(event.key==='Enter') sendOtpCode()"
                           class="block w-full pl-10 pr-3 py-3.5 text-slate-900 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm">
                </div>
                <p class="mt-2 text-xs text-slate-500 flex items-center gap-1.5">
                    <i class="fa-solid fa-lock text-slate-400"></i>
                    A secure 6-digit passcode will be sent to your registered email address.
                </p>
            </div>

            <button type="button" id="sendOtpBtn" onclick="sendOtpCode()" 
                    class="w-full py-3.5 px-4 bg-blue-900 hover:bg-blue-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2 text-sm">
                <span id="sendOtpBtnText">Send Passcode to Email</span>
                <i class="fa-solid fa-paper-plane text-xs"></i>
            </button>
        </div>

        <!-- Step 2: 6-Digit Code Verification (revealed once sent) -->
        <div id="otpStep2" class="hidden space-y-5 pt-3 border-t border-slate-200">
            <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl text-blue-900 text-xs flex items-start gap-2.5">
                <i class="fa-solid fa-envelope-open-text text-base text-blue-700 mt-0.5"></i>
                <div>
                    <span class="font-bold">Passcode Dispatched</span>
                    <p id="otpSentNotice" class="mt-0.5 text-slate-600">Please check your registered Email inbox (and spam folder).</p>
                </div>
            </div>

            <div>
                <label for="otp_code_input" class="block text-xs font-bold uppercase tracking-wider text-slate-700 text-center">
                    Enter 6-Digit Passcode
                </label>
                <input id="otp_code_input" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" 
                       placeholder="••••••"
                       onkeydown="if(event.key==='Enter') verifyOtpCode()"
                       class="mt-2 block w-full text-center tracking-[0.5em] text-3xl font-black py-3 text-slate-900 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
            </div>

            <button type="button" id="verifyOtpBtn" onclick="verifyOtpCode()" 
                    class="w-full py-3.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-sm transition-all flex items-center justify-center gap-2 shadow-md">
                <span id="verifyOtpBtnText">Verify & Access Privacy Center</span>
                <i class="fa-solid fa-check"></i>
            </button>

            <div class="flex items-center justify-between text-xs text-slate-600 pt-1">
                <button type="button" onclick="changeIdentifier()" class="text-slate-500 hover:text-slate-900 flex items-center gap-1">
                    <i class="fa-solid fa-arrow-left"></i> Change Email
                </button>
                <button type="button" id="resendOtpBtn" onclick="sendOtpCode()" class="text-blue-900 font-semibold hover:underline">
                    Resend Code
                </button>
            </div>
        </div>

        <div class="pt-5 border-t border-slate-200 text-center text-xs text-slate-500 space-y-1.5">
            <p class="flex items-center justify-center gap-1.5 text-slate-600 font-medium">
                <i class="fa-solid fa-shield-check text-blue-900"></i>
                Password-Free UK GDPR Dual-Factor Authentication
            </p>
            <p>Need help accessing your records? <a href="{{ route('contact') }}" class="text-blue-900 font-semibold underline">Contact Community Secretary</a></p>
        </div>
    </div>
</div>

<script>
function showAlert(message, type = 'error') {
    const alertBox = document.getElementById('otpStatusAlert');
    const alertIcon = document.getElementById('otpStatusIcon');
    const alertMsg = document.getElementById('otpStatusMessage');

    alertBox.classList.remove('hidden', 'bg-rose-50', 'border-rose-200', 'text-rose-800', 'bg-emerald-50', 'border-emerald-200', 'text-emerald-800');
    alertIcon.className = '';

    if (type === 'success') {
        alertBox.classList.add('bg-emerald-50', 'border', 'border-emerald-200', 'text-emerald-800');
        alertIcon.className = 'fa-solid fa-circle-check text-emerald-600 text-lg mt-0.5';
    } else {
        alertBox.classList.add('bg-rose-50', 'border', 'border-rose-200', 'text-rose-800');
        alertIcon.className = 'fa-solid fa-circle-exclamation text-rose-600 text-lg mt-0.5';
    }

    alertMsg.textContent = message;
}

function hideAlert() {
    document.getElementById('otpStatusAlert').classList.add('hidden');
}

let currentChallengeUuid = null;

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const input = document.querySelector('input[name="_token"]');
    if (input && input.value) return input.value;
    return '{{ csrf_token() }}';
}

async function sendOtpCode() {
    hideAlert();
    const identifier = document.getElementById('otp_identifier').value.trim();
    if (!identifier || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identifier)) {
        showAlert('Please enter your valid registered email address.');
        return;
    }

    const btn = document.getElementById('sendOtpBtn');
    const btnText = document.getElementById('sendOtpBtnText');
    btn.disabled = true;
    btnText.textContent = 'Dispatching passcode...';

    try {
        const response = await fetch("{{ route('member.login.otp.send') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ identifier, _token: getCsrfToken() })
        });

        if (response.status === 419) {
            showAlert('Your session refreshed. Retrying request...', 'error');
            setTimeout(() => window.location.reload(), 800);
            return;
        }

        const data = await response.json();

        if (data.success) {
            currentChallengeUuid = data.challenge_uuid || null;
            document.getElementById('otpStep1').classList.add('opacity-60', 'pointer-events-none');
            document.getElementById('otpStep2').classList.remove('hidden');
            document.getElementById('otpSentNotice').textContent = data.message;
            showAlert(data.message, 'success');
            setTimeout(() => { document.getElementById('otp_code_input').focus(); }, 150);
        } else {
            showAlert(data.message || 'Unable to locate membership record. Please verify your details.');
        }
    } catch (e) {
        showAlert('Communication error. Please check your internet connection or try again shortly.');
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Send 6-Digit Passcode';
    }
}

function changeIdentifier() {
    document.getElementById('otpStep1').classList.remove('opacity-60', 'pointer-events-none');
    document.getElementById('otpStep2').classList.add('hidden');
    hideAlert();
    document.getElementById('otp_identifier').focus();
}

async function verifyOtpCode() {
    hideAlert();
    const otp = document.getElementById('otp_code_input').value.trim();
    if (otp.length !== 6 || !/^\d{6}$/.test(otp)) {
        showAlert('Please enter the full 6-digit numeric passcode.');
        return;
    }

    const btn = document.getElementById('verifyOtpBtn');
    const btnText = document.getElementById('verifyOtpBtnText');
    btn.disabled = true;
    btnText.textContent = 'Verifying passcode...';

    try {
        const response = await fetch("{{ route('member.login.otp.verify') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ 
                otp: otp,
                challenge_uuid: currentChallengeUuid,
                _token: getCsrfToken()
            })
        });

        if (response.status === 419) {
            showAlert('Your session refreshed. Please sign in again.', 'error');
            setTimeout(() => window.location.reload(), 800);
            return;
        }

        const data = await response.json();

        if (data.success && data.redirect) {
            showAlert('Passcode verified! Redirecting...', 'success');
            window.location.href = data.redirect;
        } else {
            showAlert(data.message || 'Invalid or expired passcode. Please try again.');
        }
    } catch (e) {
        showAlert('Verification failed due to a network error. Please try again.');
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Verify & Access Privacy Center';
    }
}
</script>
@endsection
