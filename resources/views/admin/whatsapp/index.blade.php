@extends('layouts.admin')

@section('page_title', 'WhatsApp Automation Hub')

@section('content')
<div class="container-fluid">

    <!-- Top Alert if Disabled -->
    @if(!$settings['enabled'])
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4" style="border-radius:12px;">
        <i class="fas fa-exclamation-triangle fa-2x me-3 text-warning"></i>
        <div>
            <h6 class="mb-0 fw-bold">WhatsApp Automation is Currently Disabled</h6>
            <small class="text-muted">Notifications are paused. You can enable WhatsApp automation in <a href="{{ route('admin.config.settings') }}#tab-whatsapp" class="fw-bold text-dark text-decoration-underline">Global Settings</a>.</small>
        </div>
    </div>
    @endif

    <!-- Header Stats Row -->
    <div class="row g-3 mb-4">
        <!-- Status Card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px; background:linear-gradient(135deg, #0f172a, #1e293b); color:#fff;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="text-xs uppercase fw-bold tracking-wider opacity-75">Device State</span>
                        <div id="statusBadgeContainer">
                            @if(($status['connected'] ?? false))
                                <span class="badge bg-success px-3 py-2 text-xs rounded-pill"><i class="fas fa-circle me-1 small"></i> Connected</span>
                            @elseif(($status['hasQr'] ?? false))
                                <span class="badge bg-warning text-dark px-3 py-2 text-xs rounded-pill"><i class="fas fa-qrcode me-1"></i> Scan QR Code</span>
                            @else
                                <span class="badge bg-danger px-3 py-2 text-xs rounded-pill"><i class="fas fa-circle me-1 small"></i> Offline</span>
                            @endif
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1" id="deviceStatusText">
                        {{ ($status['connected'] ?? false) ? 'Ready & Active' : (($status['hasQr'] ?? false) ? 'Pairing Required' : 'Disconnected') }}
                    </h3>
                    <p class="text-xs text-white-50 mb-3" id="deviceSubText">
                        @if(($status['connected'] ?? false))
                            Linked as: <strong class="text-white">{{ $status['user']['name'] ?? $status['user']['id'] ?? 'WhatsApp Account' }}</strong>
                        @else
                            Scan the live QR code below to connect your WhatsApp device.
                        @endif
                    </p>
                    <div class="d-flex gap-2 mt-auto">
                        <button id="btnRefreshStatus" class="btn btn-sm btn-outline-light rounded-pill px-3">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                        @if(($status['connected'] ?? false))
                        <button id="btnLogoutSession" class="btn btn-sm btn-outline-danger rounded-pill px-3 text-white">
                            <i class="fas fa-unlink me-1"></i> Unlink Device
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Automation Rules Card -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-body p-4">
                    <span class="text-xs uppercase fw-bold tracking-wider text-muted d-block mb-3">Active Notification Triggers</span>
                    <ul class="list-unstyled mb-0 space-y-2">
                        <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                            <span class="text-sm"><i class="fas fa-id-card text-primary me-2"></i> Member ID Cards</span>
                            @if($settings['notify_id_card'])
                                <span class="badge bg-success-subtle text-success text-xs"><i class="fas fa-check"></i> Enabled</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary text-xs">Disabled</span>
                            @endif
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                            <span class="text-sm"><i class="fas fa-ticket-alt text-warning me-2"></i> Event Ticket PDFs</span>
                            @if($settings['notify_event_ticket'])
                                <span class="badge bg-success-subtle text-success text-xs"><i class="fas fa-check"></i> Enabled</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary text-xs">Disabled</span>
                            @endif
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                            <span class="text-sm"><i class="fas fa-key text-info me-2"></i> Verification OTPs</span>
                            @if($settings['notify_otp'])
                                <span class="badge bg-success-subtle text-success text-xs"><i class="fas fa-check"></i> Enabled</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary text-xs">Disabled</span>
                            @endif
                        </li>
                        <li class="d-flex justify-content-between align-items-center py-1">
                            <span class="text-sm"><i class="fas fa-shield-alt text-danger me-2"></i> Admin Security Alerts</span>
                            @if($settings['notify_admin_security'])
                                <span class="badge bg-success-subtle text-success text-xs"><i class="fas fa-check"></i> Enabled</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary text-xs">Disabled</span>
                            @endif
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Quick Config Overview -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-body p-4">
                    <span class="text-xs uppercase fw-bold tracking-wider text-muted d-block mb-3">Service Gateway Configuration</span>
                    <div class="mb-2">
                        <label class="text-xs text-muted mb-0">Daemon Endpoint</label>
                        <div class="font-monospace text-xs fw-bold text-dark">{{ $settings['server_url'] }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-xs text-muted mb-0">Global Automation</label>
                        <div>
                            @if($settings['enabled'])
                                <span class="badge bg-success text-xs">MASTER SWITCH: ON</span>
                            @else
                                <span class="badge bg-danger text-xs">MASTER SWITCH: OFF</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('admin.config.settings') }}#tab-whatsapp" class="btn btn-sm btn-primary rounded-pill w-100">
                        <i class="fas fa-sliders-h me-1"></i> Configure in Global Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Working Row: QR Code / Pairing and Test Dispatcher -->
    <div class="row g-4">
        <!-- QR Pairing Panel -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="fab fa-whatsapp text-success me-2"></i> Device Pairing & Connection</h5>
                        <p class="text-xs text-muted mb-0">Link your physical WhatsApp account to send transactional messages</p>
                    </div>
                    <span id="qrCountdown" class="badge bg-light text-muted small px-3 py-1">Auto-refresh: <span id="timerVal">15</span>s</span>
                </div>
                <div class="card-body p-4 text-center">

                    <!-- Connected State Panel -->
                    <div id="panelConnected" class="{{ ($status['connected'] ?? false) ? '' : 'd-none' }}">
                        <div class="py-5">
                            <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle mb-3" style="width:90px;height:90px;">
                                <i class="fas fa-check-circle fa-3x"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-2">WhatsApp Device is Connected!</h4>
                            <p class="text-muted text-sm max-w-md mx-auto mb-4">
                                The system is connected to the WhatsApp network. Automatic member card deliveries, event tickets, and OTP messages will be dispatched from this account.
                            </p>
                            <button id="btnUnlinkInside" class="btn btn-outline-danger btn-sm rounded-pill px-4">
                                <i class="fas fa-unlink me-1"></i> Unlink This Device
                            </button>
                        </div>
                    </div>

                    <!-- QR Code Scanning Panel -->
                    <div id="panelQr" class="{{ ($status['connected'] ?? false) ? 'd-none' : '' }}">
                        <div class="my-3">
                            <div id="qrContainer" class="d-inline-block p-3 bg-white border rounded-3 shadow-sm position-relative" style="min-width:280px; min-height:280px;">
                                <img id="qrImage" src="" alt="WhatsApp QR Code" class="img-fluid d-none" style="width:260px; height:260px;">
                                <div id="qrSpinner" class="d-flex flex-column align-items-center justify-content-center" style="height:260px;">
                                    <div class="spinner-border text-success mb-3" role="status"></div>
                                    <span class="text-xs text-muted">Contacting WhatsApp Daemon...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="bg-light p-3 rounded-3 text-start mx-auto mt-4" style="max-width:420px;">
                            <h6 class="fw-bold text-xs uppercase tracking-wider text-muted mb-2"><i class="fas fa-info-circle me-1 text-primary"></i> Pairing Instructions</h6>
                            <ol class="text-xs text-muted mb-0 ps-3 space-y-1">
                                <li>Open <strong>WhatsApp</strong> on your mobile phone.</li>
                                <li>Tap <strong>Settings</strong> (or the 3 vertical dots) &gt; <strong>Linked Devices</strong>.</li>
                                <li>Tap <strong>Link a Device</strong> and point your camera at this QR code.</li>
                                <li>Once scanned, the status will automatically update to <strong>Connected</strong>.</li>
                            </ol>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Test Dispatcher Panel -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold mb-1"><i class="fas fa-paper-plane text-primary me-2"></i> Live Message Dispatcher</h5>
                    <p class="text-xs text-muted mb-0">Verify your connection by sending a real-time test message</p>
                </div>
                <div class="card-body p-4">
                    <form id="formSendTest">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-muted uppercase">Recipient Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-end-0"><i class="fas fa-mobile-alt"></i></span>
                                <input type="text" name="phone" id="testPhone" class="form-control" placeholder="e.g. 07123456789 or +447123456789" required>
                            </div>
                            <small class="text-muted text-xs">Supports UK (07xxx / 447xxx) and international formats.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-xs fw-bold text-muted uppercase">Test Message Content</label>
                            <textarea name="message" id="testMessage" rows="5" class="form-control" required>Hello from Plymouth Malayalee Community Club (PMCC-UK)! 🌟

This is a test notification confirming that our WhatsApp automation service is active and operating correctly.

🌐 https://pmccuk.org</textarea>
                        </div>

                        <div id="testAlert" class="alert d-none mb-3 text-xs" style="border-radius:10px;"></div>

                        <button type="submit" id="btnSubmitTest" class="btn btn-success w-100 py-2.5 rounded-pill fw-bold">
                            <i class="fab fa-whatsapp me-2"></i> Send WhatsApp Test Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- JavaScript Engine for Real-Time Polling & Actions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrImage = document.getElementById('qrImage');
    const qrSpinner = document.getElementById('qrSpinner');
    const panelConnected = document.getElementById('panelConnected');
    const panelQr = document.getElementById('panelQr');
    const statusBadge = document.getElementById('statusBadgeContainer');
    const statusText = document.getElementById('deviceStatusText');
    const timerVal = document.getElementById('timerVal');
    const formSendTest = document.getElementById('formSendTest');
    const testAlert = document.getElementById('testAlert');
    const btnSubmitTest = document.getElementById('btnSubmitTest');

    let countdown = 15;
    let pollInterval = null;

    // ── Poll QR & Status ──
    function fetchQrAndStatus() {
        fetch('{{ route('admin.whatsapp.qr') }}')
            .then(res => res.json())
            .then(data => {
                if (data.connected) {
                    // Connected
                    panelConnected.classList.remove('d-none');
                    panelQr.classList.add('d-none');
                    statusBadge.innerHTML = '<span class="badge bg-success px-3 py-2 text-xs rounded-pill"><i class="fas fa-circle me-1 small"></i> Connected</span>';
                    statusText.textContent = 'Ready & Active';
                } else if (data.qr) {
                    // Show QR
                    panelConnected.classList.add('d-none');
                    panelQr.classList.remove('d-none');
                    qrImage.src = data.qr;
                    qrImage.classList.remove('d-none');
                    qrSpinner.classList.add('d-none');
                    statusBadge.innerHTML = '<span class="badge bg-warning text-dark px-3 py-2 text-xs rounded-pill"><i class="fas fa-qrcode me-1"></i> Scan QR Code</span>';
                    statusText.textContent = 'Pairing Required';
                } else {
                    // Connecting / Disconnected
                    panelConnected.classList.add('d-none');
                    panelQr.classList.remove('d-none');
                    qrImage.classList.add('d-none');
                    qrSpinner.classList.remove('d-none');
                    statusBadge.innerHTML = '<span class="badge bg-danger px-3 py-2 text-xs rounded-pill"><i class="fas fa-circle me-1 small"></i> Disconnected</span>';
                    statusText.textContent = 'Disconnected';
                }
            })
            .catch(err => {
                console.warn('[WhatsApp Poll Error]', err);
            });
    }

    // Initial Fetch
    fetchQrAndStatus();

    // Timer Loop
    pollInterval = setInterval(function() {
        countdown--;
        if (timerVal) timerVal.textContent = countdown;
        if (countdown <= 0) {
            countdown = 15;
            fetchQrAndStatus();
        }
    }, 1000);

    // Refresh Button
    document.getElementById('btnRefreshStatus')?.addEventListener('click', function() {
        countdown = 15;
        fetchQrAndStatus();
    });

    // Logout / Unlink Button
    function doLogout() {
        if (!confirm('Are you sure you want to unlink and disconnect this WhatsApp account?')) {
            return;
        }

        fetch('{{ route('admin.whatsapp.logout') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message || 'Device unlinked.');
            countdown = 2;
            fetchQrAndStatus();
        })
        .catch(err => alert('Error unlinking session: ' + err));
    }

    document.getElementById('btnLogoutSession')?.addEventListener('click', doLogout);
    document.getElementById('btnUnlinkInside')?.addEventListener('click', doLogout);

    // Test Message Dispatch
    formSendTest?.addEventListener('submit', function(e) {
        e.preventDefault();
        const phone = document.getElementById('testPhone').value;
        const message = document.getElementById('testMessage').value;

        btnSubmitTest.disabled = true;
        btnSubmitTest.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Dispatching...';
        testAlert.className = 'alert d-none mb-3 text-xs';

        fetch('{{ route('admin.whatsapp.send-test') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ phone: phone, message: message })
        })
        .then(res => res.json())
        .then(data => {
            btnSubmitTest.disabled = false;
            btnSubmitTest.innerHTML = '<i class="fab fa-whatsapp me-2"></i> Send WhatsApp Test Message';

            if (data.success) {
                testAlert.className = 'alert alert-success mb-3 text-xs';
                testAlert.textContent = data.message;
            } else {
                testAlert.className = 'alert alert-danger mb-3 text-xs';
                testAlert.textContent = data.message || 'Failed to dispatch message.';
            }
        })
        .catch(err => {
            btnSubmitTest.disabled = false;
            btnSubmitTest.innerHTML = '<i class="fab fa-whatsapp me-2"></i> Send WhatsApp Test Message';
            testAlert.className = 'alert alert-danger mb-3 text-xs';
            testAlert.textContent = 'Network or server error: ' + err;
        });
    });
});
</script>
@endsection
