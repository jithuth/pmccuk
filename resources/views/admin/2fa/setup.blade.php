@extends('layouts.admin')

@section('page_title', 'Two-Factor Authentication Setup')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">

        @if($enabled)
        {{-- ── 2FA is ON ── --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="badge badge-success mr-2" style="font-size:11px;">ACTIVE</span>
                Two-Factor Authentication
            </div>
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <div class="mx-auto d-flex align-items-center justify-content-center rounded-circle"
                         style="width:80px;height:80px;background:linear-gradient(135deg,#22c55e,#16a34a);">
                        <i class="fas fa-shield-alt text-white" style="font-size:28px;"></i>
                    </div>
                </div>
                <h4 class="font-weight-bold text-dark mb-2">Your account is protected</h4>
                <p class="text-muted mb-4">2FA is currently <strong>enabled</strong> on this admin account.<br>
                You will be asked for a code every time you sign in.</p>

                <button class="btn btn-danger" data-toggle="modal" data-target="#disableModal">
                    <i class="fas fa-times-circle mr-2"></i> Disable 2FA
                </button>
            </div>
        </div>

        {{-- Disable Modal --}}
        <div class="modal fade" id="disableModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:14px;border:none;overflow:hidden;">
                    <div class="modal-header" style="background:#dc3545;">
                        <h5 class="modal-title text-white"><i class="fas fa-exclamation-triangle mr-2"></i>Disable 2FA</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">This will remove 2FA protection from your account. Please enter your password to confirm.</p>
                        <form action="{{ route('admin.2fa.disable') }}" method="POST" id="disableForm">
                            @csrf
                            @method('DELETE')
                            <div class="form-group">
                                <label class="font-weight-bold text-dark">Current Password</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Enter your password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                        <button class="btn btn-danger btn-sm" onclick="document.getElementById('disableForm').submit()">
                            <i class="fas fa-unlock mr-1"></i> Yes, Disable 2FA
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @else
        {{-- ── 2FA is OFF — show setup ── --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="badge badge-warning mr-2" style="font-size:11px;">NOT ACTIVE</span>
                Enable Two-Factor Authentication
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="font-weight-bold mb-2">Secure your admin account with 2FA</h5>
                        <p class="text-muted small mb-3">
                            Use an authenticator app like <strong>Google Authenticator</strong> or <strong>Authy</strong>
                            to scan the QR code below. You'll be asked for a one-time code on every login.
                        </p>

                        <div class="alert alert-light border mb-3 small">
                            <strong>Step 1:</strong> Scan the QR code with your authenticator app<br>
                            <strong>Step 2:</strong> Enter the 6-digit code to confirm<br>
                            <strong>Step 3:</strong> 2FA will be active immediately
                        </div>

                        <div class="mb-3">
                            <label class="font-weight-bold small text-muted d-block mb-1">Or enter this secret key manually:</label>
                            <code class="bg-light px-3 py-2 rounded d-inline-block" style="font-size:14px;letter-spacing:2px;">
                                {{ $secret }}
                            </code>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <img src="{{ $qrImageUrl }}"
                             alt="2FA QR Code" class="border rounded p-1"
                             style="width:180px;height:180px;">
                        <p class="text-muted small mt-2">Scan with your app</p>
                    </div>
                </div>

                <hr>

                <form action="{{ route('admin.2fa.confirm') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Enter the 6-digit code from your app to activate:</label>
                        <div class="input-group" style="max-width:240px;">
                            <input type="text" name="otp"
                                   class="form-control text-center font-weight-bold @error('otp') is-invalid @enderror"
                                   placeholder="000000"
                                   maxlength="6" inputmode="numeric"
                                   style="font-size:20px;letter-spacing:6px;border-radius:8px 0 0 8px;">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-success" style="border-radius:0 8px 8px 0;">
                                    <i class="fas fa-check"></i> Activate
                                </button>
                            </div>
                            @error('otp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
