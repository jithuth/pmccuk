@extends('layouts.auth')

@section('title', '2FA Verification')

@section('content')
<div class="login-box" style="width:380px;">
    <div class="card shadow-lg border-0" style="border-radius:16px;overflow:hidden;">

        <!-- Header -->
        <div class="card-header text-center py-4" style="background:linear-gradient(135deg,#1a2845,#2563eb);border:none;">
            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                 style="width:56px;height:56px;background:rgba(255,255,255,0.15);border-radius:50%;border:2px solid rgba(255,255,255,0.4);">
                <i class="fas fa-shield-alt" style="font-size:22px;color:#f59e0b;"></i>
            </div>
            <h4 class="text-white font-weight-bold mb-1">Two-Factor Authentication</h4>
            <p class="text-white-50 small mb-0" id="mfaDesc">Enter the 6-digit code from your authenticator app</p>
        </div>

        <div class="card-body px-4 py-4">
            @if($errors->any())
                <div class="alert alert-danger text-center small py-2">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('admin.2fa.verify', [], false) }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <label class="text-muted small font-weight-bold mb-1" id="mfaLabel">Authentication Code</label>
                    <input type="text" name="otp" id="otp"
                           class="form-control form-control-lg text-center font-weight-bold @error('otp') is-invalid @enderror"
                           placeholder="• • • • • •"
                           autocomplete="one-time-code"
                           autofocus
                           style="font-size:22px;letter-spacing:6px;border-radius:10px;">
                    @error('otp')
                        <div class="invalid-feedback text-center">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-block btn-lg w-100 text-white font-weight-bold mt-2"
                        style="background:linear-gradient(135deg,#f59e0b,#f97316);border:none;border-radius:10px;">
                    <i class="fas fa-unlock-alt mr-2"></i> Verify & Sign In
                </button>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                    <button type="button" class="btn btn-link p-0 text-muted small" onclick="toggleAdminRecovery()" id="recoveryToggle">
                        <i class="fas fa-key mr-1"></i> Use Recovery Code
                    </button>
                    <a href="{{ route('admin.login') }}" class="text-muted small">
                        <i class="fas fa-arrow-left mr-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <p class="text-center text-muted small mt-3">
        <i class="fas fa-lock mr-1"></i> PMCC Central Security Management
    </p>
</div>

<script>
let recoveryMode = false;
function toggleAdminRecovery() {
    recoveryMode = !recoveryMode;
    const input = document.getElementById('otp');
    const label = document.getElementById('mfaLabel');
    const desc = document.getElementById('mfaDesc');
    const toggle = document.getElementById('recoveryToggle');

    if (recoveryMode) {
        label.innerText = 'Emergency Recovery Code';
        desc.innerText = 'Enter your 8-character single-use recovery code';
        input.placeholder = 'XXXX-XXXX';
        input.style.letterSpacing = '2px';
        input.value = '';
        toggle.innerHTML = '<i class="fas fa-mobile-alt mr-1"></i> Use App Code';
    } else {
        label.innerText = 'Authentication Code';
        desc.innerText = 'Enter the 6-digit code from your authenticator app';
        input.placeholder = '• • • • • •';
        input.style.letterSpacing = '6px';
        input.value = '';
        toggle.innerHTML = '<i class="fas fa-key mr-1"></i> Use Recovery Code';
    }
    input.focus();
}
</script>
@endsection
