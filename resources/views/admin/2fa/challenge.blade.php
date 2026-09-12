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
            <p class="text-white-50 small mb-0">Enter the 6-digit code from your authenticator app</p>
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
                    <label class="text-muted small font-weight-bold mb-1">Authentication Code</label>
                    <input type="text" name="otp" id="otp"
                           class="form-control form-control-lg text-center font-weight-bold @error('otp') is-invalid @enderror"
                           placeholder="• • • • • •"
                           maxlength="6"
                           inputmode="numeric"
                           pattern="[0-9]{6}"
                           autocomplete="one-time-code"
                           autofocus
                           style="font-size:24px;letter-spacing:8px;border-radius:10px;">
                    @error('otp')
                        <div class="invalid-feedback text-center">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-block btn-lg w-100 text-white font-weight-bold mt-2"
                        style="background:linear-gradient(135deg,#f59e0b,#f97316);border:none;border-radius:10px;">
                    <i class="fas fa-unlock-alt mr-2"></i> Verify & Sign In
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="{{ route('admin.login') }}" class="text-muted small">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <p class="text-center text-muted small mt-3">
        <i class="fas fa-lock mr-1"></i> This code changes every 30 seconds
    </p>
</div>

<style>
.login-page { background: linear-gradient(135deg, #0f1e35, #1a2845) !important; }
</style>
@endsection
