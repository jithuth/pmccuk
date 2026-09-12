@extends('layouts.admin')

@section('page_title', 'WhatsApp Automation Hub')

@section('styles')
<style>
    /* ── Custom WhatsApp Hub Aesthetic ── */
    :root {
        --wa-green: #25D366;
        --wa-dark-green: #128C7E;
        --wa-teal: #075E54;
        --wa-light-green: #dcf8c6;
        --wa-bg-light: #f0fdf4;
    }

    .wa-card {
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.06);
        background: #ffffff;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .wa-card:hover {
        box-shadow: 0 8px 30px -4px rgba(0, 0, 0, 0.08);
    }

    .wa-gradient-card {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f231e 100%);
        border: 1px solid rgba(37, 211, 102, 0.2);
        color: #ffffff;
    }

    .wa-icon-box {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .wa-icon-whatsapp {
        background: linear-gradient(135deg, #25D366, #128C7E);
        color: white;
        box-shadow: 0 4px 14px rgba(37, 211, 102, 0.35);
    }

    .wa-icon-rules {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);
    }

    .wa-icon-server {
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        color: white;
        box-shadow: 0 4px 14px rgba(139, 92, 246, 0.3);
    }

    /* Pulse dot */
    .pulse-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        position: relative;
    }
    .pulse-dot.pulse-green {
        background-color: #22c55e;
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
        animation: pulse-animation 2s infinite;
    }
    .pulse-dot.pulse-amber {
        background-color: #f59e0b;
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
        animation: pulse-animation 2s infinite;
    }
    .pulse-dot.pulse-red {
        background-color: #ef4444;
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
        animation: pulse-animation 2s infinite;
    }
    @keyframes pulse-animation {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    /* QR Viewfinder Frame */
    .qr-frame {
        position: relative;
        background: #ffffff;
        padding: 16px;
        border-radius: 20px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06), 0 0 0 1px rgba(0,0,0,0.04);
        display: inline-block;
    }
    .qr-frame::before, .qr-frame::after,
    .qr-frame > .corner-bl, .qr-frame > .corner-br {
        content: "";
        position: absolute;
        width: 22px;
        height: 22px;
        border-color: #25D366;
        border-style: solid;
        pointer-events: none;
    }
    .qr-frame::before { top: 6px; left: 6px; border-width: 3px 0 0 3px; border-radius: 8px 0 0 0; }
    .qr-frame::after { top: 6px; right: 6px; border-width: 3px 3px 0 0; border-radius: 0 8px 0 0; }
    .qr-frame > .corner-bl { bottom: 6px; left: 6px; border-width: 0 0 3px 3px; border-radius: 0 0 0 8px; }
    .qr-frame > .corner-br { bottom: 6px; right: 6px; border-width: 0 3px 3px 0; border-radius: 0 0 8px 0; }

    /* Template chips */
    .template-chip {
        display: inline-flex;
        align-items: center;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
    }
    .template-chip:hover {
        background: #e2e8f0;
        color: #0f172a;
        transform: translateY(-1px);
    }
    .template-chip.active {
        background: #dcfce7;
        color: #166534;
        border-color: #86efac;
    }

    /* Send Button */
    .btn-wa-send {
        background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
        border: none;
        color: #ffffff;
        font-weight: 700;
        letter-spacing: 0.3px;
        box-shadow: 0 6px 20px rgba(37, 211, 102, 0.35);
        transition: all 0.2s ease;
    }
    .btn-wa-send:hover {
        background: linear-gradient(135deg, #22c55e 0%, #0f766e 100%);
        box-shadow: 0 8px 25px rgba(37, 211, 102, 0.45);
        color: #ffffff;
        transform: translateY(-1px);
    }
    .btn-wa-send:active {
        transform: translateY(0);
    }
</style>
@endsection

@section('content')
<div class="px-1">

    <!-- Top Alert if Master Switch is Disabled -->
    @if(!$settings['enabled'])
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4 p-3 rounded-4">
        <div class="wa-icon-box bg-warning text-dark me-3 rounded-circle flex-shrink-0" style="width:40px;height:40px;font-size:18px;">
            <i class="fas fa-pause"></i>
        </div>
        <div class="flex-grow-1">
            <h6 class="mb-0 fw-bold text-dark">WhatsApp Automation is Paused</h6>
            <span class="text-muted small">Transactional messaging triggers are currently disabled. You can re-enable the master toggle in <a href="{{ route('admin.config.settings') }}#tab-whatsapp" class="fw-bold text-dark text-decoration-underline">Global Settings</a>.</span>
        </div>
        <a href="{{ route('admin.config.settings') }}#tab-whatsapp" class="btn btn-sm btn-dark rounded-pill px-3 ms-2">
            Turn On
        </a>
    </div>
    @endif

    <!-- ── TOP STATS ROW: 3 Equal-Height Aligned Cards ── -->
    <div class="row g-3 mb-4">

        <!-- 1. Device Status Card -->
        <div class="col-lg-4 col-md-6">
            <div class="card wa-card wa-gradient-card h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="wa-icon-box wa-icon-whatsapp">
                                    <i class="fab fa-whatsapp"></i>
                                </span>
                                <div>
                                    <div class="text-xs uppercase fw-bold tracking-wider text-white-50">Connection State</div>
                                    <div class="fw-bold text-white fs-6">WhatsApp Daemon</div>
                                </div>
                            </div>
                            <div id="statusBadgeContainer">
                                @if(($status['connected'] ?? false))
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill text-xs fw-bold d-inline-flex align-items-center gap-1.5">
                                        <span class="pulse-dot pulse-green"></span> Connected
                                    </span>
                                @elseif(($status['hasQr'] ?? false))
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1.5 rounded-pill text-xs fw-bold d-inline-flex align-items-center gap-1.5">
                                        <span class="pulse-dot pulse-amber"></span> Scan QR
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill text-xs fw-bold d-inline-flex align-items-center gap-1.5">
                                        <span class="pulse-dot pulse-red"></span> Offline
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="my-2">
                            <h4 class="fw-black mb-1 text-white" id="deviceStatusText">
                                {{ ($status['connected'] ?? false) ? 'Connected & Ready' : (($status['hasQr'] ?? false) ? 'Pairing Required' : 'Offline') }}
                            </h4>
                            <p class="text-xs text-white-50 mb-0 lh-base" id="deviceSubText">
                                @if(($status['connected'] ?? false))
                                    Linked account: <strong class="text-white">{{ $status['user']['name'] ?? $status['user']['id'] ?? 'Authenticated Device' }}</strong>
                                @elseif(($status['hasQr'] ?? false))
                                    Multi-Device socket online. Scan the viewfinder QR code on this page.
                                @else
                                    Daemon service running. Connecting socket...
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 pt-3 mt-3 border-top border-white-10">
                        <button id="btnRefreshStatus" class="btn btn-xs btn-outline-light rounded-pill px-3 py-1.5 text-xs fw-bold d-inline-flex align-items-center gap-1">
                            <i class="fas fa-sync-alt" id="refreshIcon"></i> Refresh
                        </button>
                        @if(($status['connected'] ?? false))
                        <button id="btnLogoutSession" class="btn btn-xs btn-outline-danger rounded-pill px-3 py-1.5 text-xs fw-bold ms-auto">
                            <i class="fas fa-unlink me-1"></i> Unlink Session
                        </button>
                        @endif
                        <span class="text-white-50 text-xs ms-auto" id="lastUpdatedText">Auto-checks active</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Automation Triggers Engine -->
        <div class="col-lg-4 col-md-6">
            <div class="card wa-card h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="wa-icon-box wa-icon-rules">
                                    <i class="fas fa-bolt"></i>
                                </span>
                                <div>
                                    <div class="text-xs uppercase fw-bold tracking-wider text-muted">Automation Engine</div>
                                    <div class="fw-bold text-dark fs-6">Transactional Rules</div>
                                </div>
                            </div>
                            <span class="badge bg-light text-secondary border px-2.5 py-1 rounded-pill text-xs fw-bold">
                                4 Triggers
                            </span>
                        </div>

                        <div class="space-y-2">
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                <span class="text-xs text-secondary d-inline-flex align-items-center gap-2">
                                    <i class="fas fa-id-card text-primary opacity-75"></i> Member ID Cards (PDF)
                                </span>
                                @if($settings['notify_id_card'])
                                    <span class="badge bg-success-subtle text-success rounded-pill text-xs px-2.5 py-1 fw-bold"><i class="fas fa-check me-1"></i>Active</span>
                                @else
                                    <span class="badge bg-light text-muted rounded-pill text-xs px-2 py-1">Off</span>
                                @endif
                            </div>

                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                <span class="text-xs text-secondary d-inline-flex align-items-center gap-2">
                                    <i class="fas fa-ticket-alt text-warning opacity-75"></i> Event Entry Passes (PDF)
                                </span>
                                @if($settings['notify_event_ticket'])
                                    <span class="badge bg-success-subtle text-success rounded-pill text-xs px-2.5 py-1 fw-bold"><i class="fas fa-check me-1"></i>Active</span>
                                @else
                                    <span class="badge bg-light text-muted rounded-pill text-xs px-2 py-1">Off</span>
                                @endif
                            </div>

                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                <span class="text-xs text-secondary d-inline-flex align-items-center gap-2">
                                    <i class="fas fa-key text-info opacity-75"></i> Booking & Renewal OTPs
                                </span>
                                @if($settings['notify_otp'])
                                    <span class="badge bg-success-subtle text-success rounded-pill text-xs px-2.5 py-1 fw-bold"><i class="fas fa-check me-1"></i>Active</span>
                                @else
                                    <span class="badge bg-light text-muted rounded-pill text-xs px-2 py-1">Off</span>
                                @endif
                            </div>

                            <div class="d-flex justify-content-between align-items-center py-1">
                                <span class="text-xs text-secondary d-inline-flex align-items-center gap-2">
                                    <i class="fas fa-shield-alt text-danger opacity-75"></i> Telegram Approval Sync
                                </span>
                                <span class="badge bg-success-subtle text-success rounded-pill text-xs px-2.5 py-1 fw-bold"><i class="fas fa-check me-1"></i>Active</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-top border-light text-end">
                        <a href="{{ route('admin.config.settings') }}#tab-whatsapp" class="text-xs text-primary fw-bold text-decoration-none">
                            Edit notification preferences <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Gateway Environment Card -->
        <div class="col-lg-4 col-md-12">
            <div class="card wa-card h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="wa-icon-box wa-icon-server">
                                    <i class="fas fa-server"></i>
                                </span>
                                <div>
                                    <div class="text-xs uppercase fw-bold tracking-wider text-muted">Service Gateway</div>
                                    <div class="fw-bold text-dark fs-6">Local Daemon IPC</div>
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill text-xs fw-bold">
                                Port 8085
                            </span>
                        </div>

                        <div class="bg-light p-3 rounded-3 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-xs text-muted">Daemon URL:</span>
                                <span class="font-monospace text-xs fw-bold text-dark">{{ $settings['server_url'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-xs text-muted">Process Manager:</span>
                                <span class="badge bg-success-subtle text-success text-xs px-2 py-0.5">PM2 Cluster (Online)</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-xs text-muted">Protocol:</span>
                                <span class="text-xs fw-bold text-dark">Baileys Multi-Device WebSocket</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 border-top border-light">
                        <a href="{{ route('admin.config.settings') }}#tab-whatsapp" class="btn btn-sm btn-outline-primary rounded-pill w-100 py-1.5 text-xs fw-bold">
                            <i class="fas fa-sliders-h me-1.5"></i> Open Full WhatsApp Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ── MAIN ROW: Aligned 2-Column Workstation ── -->
    <div class="row g-4 align-items-stretch">

        <!-- Column 1: QR Code & Pairing Hub -->
        <div class="col-lg-6 d-flex flex-column">
            <div class="card wa-card flex-grow-1">
                <div class="card-header bg-white border-bottom border-light pt-4 px-4 pb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-black text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fab fa-whatsapp text-success fs-4"></i> Device Pairing Hub
                        </h5>
                        <p class="text-xs text-muted mb-0 mt-0.5">Connect your official WhatsApp smartphone account</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="qrCountdown" class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill text-xs fw-bold">
                            Auto-refresh: <span id="timerVal" class="text-dark">15</span>s
                        </span>
                    </div>
                </div>

                <div class="card-body p-4 d-flex flex-column justify-content-center text-center">

                    <!-- CONNECTED VIEW: Clean Device Information Card -->
                    <div id="panelConnected" class="{{ ($status['connected'] ?? false) ? '' : 'd-none' }} py-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle mb-3 shadow-sm" style="width:84px;height:84px;">
                            <i class="fas fa-check-circle fa-3x"></i>
                        </div>
                        <h4 class="fw-black text-dark mb-1">Device Successfully Linked!</h4>
                        <p class="text-muted text-xs mx-auto mb-4" style="max-width:380px;">
                            The PMCC-UK platform is connected to WhatsApp. All automated member registration ID cards, event tickets, and OTP messages are dispatched instantly.
                        </p>

                        <div class="p-3 bg-light rounded-4 text-start mx-auto mb-4 border" style="max-width:380px;">
                            <div class="d-flex justify-content-between py-1 border-bottom border-light">
                                <span class="text-xs text-muted">Account Name:</span>
                                <span class="text-xs fw-bold text-dark" id="connectedUserName">{{ $status['user']['name'] ?? 'WhatsApp User' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom border-light">
                                <span class="text-xs text-muted">WhatsApp ID:</span>
                                <span class="text-xs font-monospace fw-bold text-dark" id="connectedUserId">{{ $status['user']['id'] ?? 'Authenticated' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-xs text-muted">Engine Status:</span>
                                <span class="badge bg-success text-xs">Ready for Dispatch</span>
                            </div>
                        </div>

                        <button id="btnUnlinkInside" class="btn btn-outline-danger btn-sm rounded-pill px-4 py-2 text-xs fw-bold">
                            <i class="fas fa-unlink me-1.5"></i> Disconnect / Unlink Device
                        </button>
                    </div>

                    <!-- QR CODE VIEW: Live Viewfinder QR Scanner -->
                    <div id="panelQr" class="{{ ($status['connected'] ?? false) ? 'd-none' : '' }}">
                        <div class="my-2">
                            <div class="qr-frame">
                                <span class="corner-bl"></span>
                                <span class="corner-br"></span>
                                
                                <div id="qrContainer" class="d-flex align-items-center justify-content-center" style="width:250px; height:250px;">
                                    <img id="qrImage" src="" alt="WhatsApp QR Code" class="img-fluid d-none rounded-3" style="width:240px; height:240px;">
                                    <div id="qrSpinner" class="d-flex flex-column align-items-center justify-content-center">
                                        <div class="spinner-border text-success mb-3" style="width:2.5rem; height:2.5rem;" role="status"></div>
                                        <span class="text-xs fw-bold text-muted">Streaming Live QR Code...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step-by-Step Pairing Card -->
                        <div class="bg-light p-3 rounded-4 text-start mx-auto mt-4 border border-light" style="max-width:440px;">
                            <div class="fw-bold text-xs uppercase tracking-wider text-muted mb-2.5 d-flex align-items-center gap-1.5">
                                <i class="fas fa-mobile-screen-button text-primary"></i> 4 Simple Steps to Pair
                            </div>
                            <div class="d-flex flex-column gap-2 text-xs text-secondary">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-white text-dark border rounded-circle" style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;">1</span>
                                    <span>Open <strong>WhatsApp</strong> on your mobile phone</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-white text-dark border rounded-circle" style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;">2</span>
                                    <span>Go to <strong>Settings</strong> &gt; <strong>Linked Devices</strong></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-white text-dark border rounded-circle" style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;">3</span>
                                    <span>Tap <strong>Link a Device</strong></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-white text-dark border rounded-circle" style="width:20px;height:20px;display:flex;align-items:center;justify-content:center;">4</span>
                                    <span>Point your camera at this QR code to connect</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Column 2: Live Message Dispatcher -->
        <div class="col-lg-6 d-flex flex-column">
            <div class="card wa-card flex-grow-1">
                <div class="card-header bg-white border-bottom border-light pt-4 px-4 pb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-black text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-paper-plane text-primary fs-5"></i> Live Message Dispatcher
                        </h5>
                        <p class="text-xs text-muted mb-0 mt-0.5">Send a real-time test notification to verify delivery</p>
                    </div>
                    <span class="badge bg-light text-muted border px-2.5 py-1 text-xs fw-bold">Sandbox</span>
                </div>

                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <form id="formSendTest" class="d-flex flex-column h-100 justify-content-between">
                        @csrf

                        <div>
                            <!-- Phone Number Input with UK Flag Badge -->
                            <div class="mb-3">
                                <label class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1.5">Recipient Mobile Number</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light text-dark fw-bold border-end-0 text-xs px-3">
                                        🇬🇧 +44
                                    </span>
                                    <input type="text" name="phone" id="testPhone" class="form-control form-control-lg bg-light border-start-0 text-sm fw-bold" placeholder="7123456789 (or international 91xxx)" required>
                                </div>
                                <span class="text-xs text-muted mt-1 d-block">
                                    Enter UK mobile (e.g. <code>07901296858</code> or <code>7901296858</code>) or any international number with country code.
                                </span>
                            </div>

                            <!-- Fast Template Chips -->
                            <div class="mb-3">
                                <label class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1.5 d-block">Quick Templates</label>
                                <div class="d-flex flex-wrap gap-1.5">
                                    <span class="template-chip active" data-type="ping">
                                        <i class="fas fa-bolt me-1 text-warning"></i> Test Ping
                                    </span>
                                    <span class="template-chip" data-type="member">
                                        <i class="fas fa-id-card me-1 text-primary"></i> Member Card Notice
                                    </span>
                                    <span class="template-chip" data-type="ticket">
                                        <i class="fas fa-ticket-alt me-1 text-success"></i> Ticket Ready
                                    </span>
                                    <span class="template-chip" data-type="otp">
                                        <i class="fas fa-shield-alt me-1 text-danger"></i> OTP Code
                                    </span>
                                </div>
                            </div>

                            <!-- Message Body with Live Character Counter -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1.5">
                                    <label class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-0">Message Content</label>
                                    <span class="text-xs text-muted" id="charCounter">0 characters</span>
                                </div>
                                <textarea name="message" id="testMessage" rows="5" class="form-control bg-light border-0 rounded-3 text-sm p-3" required style="resize:none;">Hello from Plymouth Malayalee Community Club (PMCC-UK)! 🌟

This is a test notification confirming that our WhatsApp automation service is active and operating correctly.

🌐 https://pmccuk.org</textarea>
                            </div>

                            <div id="testAlert" class="alert d-none mb-3 text-xs rounded-3 p-3"></div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" id="btnSubmitTest" class="btn btn-wa-send btn-lg w-100 rounded-pill py-3 text-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="fab fa-whatsapp fs-5"></i>
                                <span>Send WhatsApp Test Message</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- ── FOOTER DIAGNOSTICS BAR ── -->
    <div class="wa-card p-3.5 mt-4 d-flex flex-wrap align-items-center justify-content-between gap-3 text-xs text-muted">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="d-inline-flex align-items-center gap-1.5 text-dark fw-bold">
                <i class="fas fa-shield-halved text-success"></i> Security Architecture:
            </span>
            <span>REST Endpoints: <code class="text-dark">/send/text</code> &amp; <code class="text-dark">/send/file</code></span>
            <span>Auth: <code class="text-dark">Bearer Token (Strict)</code></span>
            <span>Fail-Safe: <code class="text-dark">Parallel Mail + Telegram (Non-blocking)</code></span>
        </div>
        <div>
            <span>Version: <strong>PMCC-WA v1.0</strong> (Baileys v6.7.18)</span>
        </div>
    </div>

</div>

<!-- ── JAVASCRIPT LOGIC ── -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrImage = document.getElementById('qrImage');
    const qrSpinner = document.getElementById('qrSpinner');
    const panelConnected = document.getElementById('panelConnected');
    const panelQr = document.getElementById('panelQr');
    const statusBadge = document.getElementById('statusBadgeContainer');
    const statusText = document.getElementById('deviceStatusText');
    const subText = document.getElementById('deviceSubText');
    const timerVal = document.getElementById('timerVal');
    const formSendTest = document.getElementById('formSendTest');
    const testAlert = document.getElementById('testAlert');
    const btnSubmitTest = document.getElementById('btnSubmitTest');
    const refreshIcon = document.getElementById('refreshIcon');
    const lastUpdatedText = document.getElementById('lastUpdatedText');
    const testMessage = document.getElementById('testMessage');
    const charCounter = document.getElementById('charCounter');
    const templateChips = document.querySelectorAll('.template-chip');

    let countdown = 15;
    let pollInterval = null;

    // Character Counter
    function updateCharCount() {
        if (charCounter && testMessage) {
            charCounter.textContent = `${testMessage.value.length} characters`;
        }
    }
    updateCharCount();
    testMessage?.addEventListener('input', updateCharCount);

    // Template Chips
    const templates = {
        ping: `Hello from Plymouth Malayalee Community Club (PMCC-UK)! 🌟\n\nThis is a test notification confirming that our WhatsApp automation service is active and operating correctly.\n\n🌐 https://pmccuk.org`,
        member: `Dear Member,\n\nCongratulations! Your PMCC-UK membership has been approved.\n\n🆔 Membership ID: PMCC-1052\n📅 Valid Until: 31 Dec 2027\n\nYour official digital ID Card is attached for entry and benefits.\n\nWarm regards,\nPMCC-UK Executive Committee`,
        ticket: `🎟️ PMCC-UK Event Ticket Confirmation\n\nDear Member,\nYour booking for 'Onam Celebration 2026' has been approved.\n\nRef: BOOK-1052\nTotal Attendees: 4 (2 Adults, 2 Kids)\n\nPlease present the attached PDF pass with QR code at the entrance desk.\n\nSee you there! 🎉`,
        otp: `PMCC-UK Verification Code: 582910\n\nUse this one-time code to complete your Event Booking verification.\n\nDo not share this code with anyone.\n(Valid for 10 minutes)`
    };

    templateChips.forEach(chip => {
        chip.addEventListener('click', function() {
            templateChips.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const type = this.getAttribute('data-type');
            if (templates[type]) {
                testMessage.value = templates[type];
                updateCharCount();
            }
        });
    });

    // ── Poll QR & Status ──
    function fetchQrAndStatus() {
        if (refreshIcon) refreshIcon.classList.add('fa-spin');

        fetch('{{ route('admin.whatsapp.qr') }}')
            .then(res => res.json())
            .then(data => {
                if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                if (lastUpdatedText) {
                    const now = new Date();
                    lastUpdatedText.textContent = `Updated: ${now.toLocaleTimeString()}`;
                }

                if (data.connected) {
                    panelConnected.classList.remove('d-none');
                    panelQr.classList.add('d-none');
                    statusBadge.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill text-xs fw-bold d-inline-flex align-items-center gap-1.5"><span class="pulse-dot pulse-green"></span> Connected</span>';
                    statusText.textContent = 'Connected & Ready';
                    subText.innerHTML = `Linked account: <strong class="text-white">${data.user?.name || data.user?.id || 'WhatsApp User'}</strong>`;
                    
                    const userNameEl = document.getElementById('connectedUserName');
                    const userIdEl = document.getElementById('connectedUserId');
                    if (userNameEl) userNameEl.textContent = data.user?.name || 'Authenticated User';
                    if (userIdEl) userIdEl.textContent = data.user?.id || 'Connected Account';
                } else if (data.qr) {
                    panelConnected.classList.add('d-none');
                    panelQr.classList.remove('d-none');
                    qrImage.src = data.qr;
                    qrImage.classList.remove('d-none');
                    qrSpinner.classList.add('d-none');
                    statusBadge.innerHTML = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1.5 rounded-pill text-xs fw-bold d-inline-flex align-items-center gap-1.5"><span class="pulse-dot pulse-amber"></span> Scan QR</span>';
                    statusText.textContent = 'Pairing Required';
                    subText.textContent = 'Multi-Device socket online. Scan the viewfinder QR code on this page.';
                } else {
                    panelConnected.classList.add('d-none');
                    panelQr.classList.remove('d-none');
                    qrImage.classList.add('d-none');
                    qrSpinner.classList.remove('d-none');
                    statusBadge.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 rounded-pill text-xs fw-bold d-inline-flex align-items-center gap-1.5"><span class="pulse-dot pulse-red"></span> Offline</span>';
                    statusText.textContent = 'Offline';
                    subText.textContent = 'Contacting daemon service...';
                }
            })
            .catch(err => {
                if (refreshIcon) refreshIcon.classList.remove('fa-spin');
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
        btnSubmitTest.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Dispatching WhatsApp Message...';
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
            btnSubmitTest.innerHTML = '<i class="fab fa-whatsapp fs-5"></i> <span>Send WhatsApp Test Message</span>';

            if (data.success) {
                testAlert.className = 'alert alert-success mb-3 text-xs border-0 bg-success-subtle text-success p-3 rounded-3 d-flex align-items-center gap-2';
                testAlert.innerHTML = `<i class="fas fa-check-circle fs-5"></i> <div><strong>Success!</strong> ${data.message}</div>`;
            } else {
                testAlert.className = 'alert alert-danger mb-3 text-xs border-0 bg-danger-subtle text-danger p-3 rounded-3 d-flex align-items-center gap-2';
                testAlert.innerHTML = `<i class="fas fa-exclamation-circle fs-5"></i> <div><strong>Failed:</strong> ${data.message || 'Could not dispatch message.'}</div>`;
            }
        })
        .catch(err => {
            btnSubmitTest.disabled = false;
            btnSubmitTest.innerHTML = '<i class="fab fa-whatsapp fs-5"></i> <span>Send WhatsApp Test Message</span>';
            testAlert.className = 'alert alert-danger mb-3 text-xs border-0 bg-danger-subtle text-danger p-3 rounded-3 d-flex align-items-center gap-2';
            testAlert.innerHTML = `<i class="fas fa-wifi fs-5"></i> <div><strong>Network Error:</strong> ${err}</div>`;
        });
    });
});
</script>
@endsection

