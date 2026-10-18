@extends('layouts.app')

@section('title', 'Member Privacy Center Login | PMCC-UK')
@section('meta_description', 'Secure sign-in portal for PMCC-UK members to manage personal data, subject access requests, and privacy rights.')

@section('content')
<div class="min-h-screen py-16 bg-slate-50 flex items-center justify-center px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl border border-slate-200">
        <div>
            <div class="w-16 h-16 bg-blue-900 text-white rounded-2xl flex items-center justify-center mx-auto text-2xl shadow-md">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h2 class="mt-4 text-center text-2xl font-extrabold text-slate-900">
                Member Privacy Center
            </h2>
            <p class="mt-2 text-center text-sm text-slate-600">
                UK GDPR Self-Service Portal for Community Members
            </p>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm space-y-1">
                <div class="font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600"></i> Authentication Error
                </div>
                <ul class="list-disc list-inside text-xs pl-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Toggle Tabs: Password vs OTP -->
        <div class="flex rounded-xl bg-slate-100 p-1 text-sm font-semibold text-slate-600">
            <button type="button" id="tabPasswordBtn" onclick="switchLoginMode('password')" class="flex-1 py-2 text-center rounded-lg bg-white shadow text-blue-900 transition-all">
                <i class="fa-solid fa-key mr-1"></i> Password
            </button>
            <button type="button" id="tabOtpBtn" onclick="switchLoginMode('otp')" class="flex-1 py-2 text-center rounded-lg hover:text-slate-900 transition-all">
                <i class="fa-solid fa-mobile-screen mr-1"></i> Passcode (OTP)
            </button>
        </div>

        <!-- 1. Password Login Form -->
        <form id="passwordLoginForm" action="{{ route('member.login.submit') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            <div>
                <label for="login_input" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Member ID, Email or Mobile</label>
                <div class="mt-1 relative rounded-xl shadow-sm">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input id="login_input" name="login" type="text" value="{{ old('login') }}" required
                           placeholder="PMCC-1001 or name@example.com"
                           class="block w-full pl-10 pr-3 py-3 text-slate-900 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm">
                </div>
            </div>

            <div>
                <label for="password_input" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Account Password</label>
                <div class="mt-1 relative rounded-xl shadow-sm">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input id="password_input" name="password" type="password" required
                           placeholder="••••••••••••"
                           class="block w-full pl-10 pr-3 py-3 text-slate-900 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm">
                </div>
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-900 focus:ring-blue-500 mr-2">
                    Remember this device (30 days)
                </label>
                <button type="button" onclick="switchLoginMode('otp')" class="text-blue-900 font-semibold hover:underline">
                    No password? Use OTP
                </button>
            </div>

            <button type="submit" class="w-full py-3.5 px-4 bg-blue-900 hover:bg-blue-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2 text-sm">
                <span>Sign In Securely</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <!-- 2. OTP Verification Form -->
        <div id="otpLoginForm" class="hidden mt-6 space-y-5">
            <div id="otpStep1">
                <label for="otp_identifier" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Membership Number or Registered Email</label>
                <div class="mt-1 relative rounded-xl shadow-sm">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-id-card"></i>
                    </span>
                    <input id="otp_identifier" type="text" placeholder="e.g. PMCC-1001 or email@domain.com"
                           class="block w-full pl-10 pr-3 py-3 text-slate-900 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 text-sm">
                </div>
                <button type="button" id="sendOtpBtn" onclick="sendOtpCode()" class="mt-3 w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition-all flex items-center justify-center gap-2">
                    <span id="sendOtpBtnText">Send 6-Digit Passcode</span>
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                </button>
            </div>

            <div id="otpStep2" class="hidden space-y-4 pt-2 border-t border-slate-200">
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-900 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-envelope-open-text text-base"></i>
                    <span id="otpSentNotice">A verification code has been dispatched.</span>
                </div>

                <div>
                    <label for="otp_code_input" class="block text-xs font-bold uppercase tracking-wider text-slate-700">Enter 6-Digit Code</label>
                    <input id="otp_code_input" type="text" maxlength="6" placeholder="123456"
                           class="mt-1 block w-full text-center tracking-widest text-2xl font-bold py-3 text-slate-900 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                </div>

                <button type="button" id="verifyOtpBtn" onclick="verifyOtpCode()" class="w-full py-3.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-sm transition-all flex items-center justify-center gap-2">
                    <span>Verify & Enter Privacy Center</span>
                    <i class="fa-solid fa-check"></i>
                </button>

                <div class="text-center">
                    <button type="button" onclick="sendOtpCode()" class="text-xs text-slate-500 hover:text-slate-800 underline">
                        Resend Code
                    </button>
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-200 text-center text-xs text-slate-500">
            <p>Protected by UK GDPR privacy controls & dual-factor verification.</p>
            <p class="mt-1">Need help accessing your records? <a href="{{ route('contact') }}" class="text-blue-900 underline">Contact Community Secretary</a></p>
        </div>
    </div>
</div>

<script>
function switchLoginMode(mode) {
    const pwTab = document.getElementById('tabPasswordBtn');
    const otpTab = document.getElementById('tabOtpBtn');
    const pwForm = document.getElementById('passwordLoginForm');
    const otpForm = document.getElementById('otpLoginForm');

    if (mode === 'password') {
        pwTab.classList.add('bg-white', 'shadow', 'text-blue-900');
        pwTab.classList.remove('text-slate-600');
        otpTab.classList.remove('bg-white', 'shadow', 'text-blue-900');
        otpTab.classList.add('text-slate-600');
        pwForm.classList.remove('hidden');
        otpForm.classList.add('hidden');
    } else {
        otpTab.classList.add('bg-white', 'shadow', 'text-blue-900');
        otpTab.classList.remove('text-slate-600');
        pwTab.classList.remove('bg-white', 'shadow', 'text-blue-900');
        pwTab.classList.add('text-slate-600');
        otpForm.classList.remove('hidden');
        pwForm.classList.add('hidden');
    }
}

async function sendOtpCode() {
    const identifier = document.getElementById('otp_identifier').value.trim();
    if (!identifier) {
        alert('Please enter your Membership Number or registered email.');
        return;
    }

    const btn = document.getElementById('sendOtpBtn');
    const btnText = document.getElementById('sendOtpBtnText');
    btn.disabled = true;
    btnText.textContent = 'Sending...';

    try {
        const response = await fetch("{{ route('member.login.otp.send') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ identifier })
        });
        const data = await response.json();

        if (data.success) {
            document.getElementById('otpStep2').classList.remove('hidden');
            document.getElementById('otpSentNotice').textContent = data.message;
        } else {
            alert(data.message || 'Failed to dispatch passcode.');
        }
    } catch (e) {
        alert('Communication error. Please try again or use password login.');
    } finally {
        btn.disabled = false;
        btnText.textContent = 'Send 6-Digit Passcode';
    }
}

async function verifyOtpCode() {
    const otp = document.getElementById('otp_code_input').value.trim();
    if (otp.length !== 6) {
        alert('Please enter the full 6-digit passcode.');
        return;
    }

    const btn = document.getElementById('verifyOtpBtn');
    btn.disabled = true;

    try {
        const response = await fetch("{{ route('member.login.otp.verify') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ otp })
        });
        const data = await response.json();

        if (data.success && data.redirect) {
            window.location.href = data.redirect;
        } else {
            alert(data.message || 'Invalid passcode.');
        }
    } catch (e) {
        alert('Verification error. Please retry.');
    } finally {
        btn.disabled = false;
    }
}
</script>
@endsection
