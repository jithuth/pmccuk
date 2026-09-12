@extends('layouts.admin')

@section('page_title', 'WhatsApp Automation Command Center')

@section('styles')
<style>
    :root {
        --wa-brand: #25D366;
        --wa-dark: #128C7E;
        --wa-deep: #075E54;
        --wa-bubble: #dcf8c6;
        --wa-bubble-dark: #005c4b;
        --wa-tick: #53bdeb;
        --card-bg: #ffffff;
        --card-border: rgba(0, 0, 0, 0.07);
    }

    /* ── Overall Container ── */
    .wa-hub-wrap {
        padding: 0 4px;
    }

    /* ── Command Hero Banner ── */
    .wa-hero-banner {
        background: radial-gradient(circle at top right, rgba(37, 211, 102, 0.15), transparent 50%),
                    linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #064e3b 100%);
        border-radius: 20px;
        padding: 24px 28px;
        color: #ffffff;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(37, 211, 102, 0.25);
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.3);
        margin-bottom: 24px;
    }

    .wa-hero-glow {
        position: absolute;
        top: -60px;
        right: -60px;
        width: 180px;
        height: 180px;
        background: radial-gradient(circle, rgba(37, 211, 102, 0.35) 0%, transparent 70%);
        border-radius: 50%;
        filter: blur(25px);
        pointer-events: none;
    }

    /* ── Cards & Elevation ── */
    .wa-elevated-card {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid var(--card-border);
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .wa-elevated-card:hover {
        box-shadow: 0 8px 30px -4px rgba(0, 0, 0, 0.08);
    }

    /* ── Pulse Radar Dot ── */
    .radar-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        position: relative;
    }
    .radar-dot.green {
        background: #22c55e;
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
        animation: radar-sweep 2s infinite;
    }
    .radar-dot.amber {
        background: #f59e0b;
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
        animation: radar-sweep 2s infinite;
    }
    .radar-dot.red {
        background: #ef4444;
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
        animation: radar-sweep 2s infinite;
    }
    @keyframes radar-sweep {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 9px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    /* ── High-Tech Viewfinder QR Box ── */
    .viewfinder-box {
        position: relative;
        background: #ffffff;
        padding: 16px;
        border-radius: 24px;
        box-shadow: 0 12px 36px -8px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.04);
        display: inline-block;
        overflow: hidden;
    }
    .viewfinder-bracket {
        position: absolute;
        width: 24px;
        height: 24px;
        border-color: #25D366;
        border-style: solid;
        pointer-events: none;
        z-index: 5;
    }
    .viewfinder-tl { top: 8px; left: 8px; border-width: 3.5px 0 0 3.5px; border-radius: 8px 0 0 0; }
    .viewfinder-tr { top: 8px; right: 8px; border-width: 3.5px 3.5px 0 0; border-radius: 0 8px 0 0; }
    .viewfinder-bl { bottom: 8px; left: 8px; border-width: 0 0 3.5px 3.5px; border-radius: 0 0 0 8px; }
    .viewfinder-br { bottom: 8px; right: 8px; border-width: 0 3.5px 3.5px 0; border-radius: 0 0 8px 0; }

    /* Laser Scanning Line */
    .laser-line {
        position: absolute;
        left: 12px;
        right: 12px;
        height: 2px;
        background: linear-gradient(90deg, transparent, #25D366 50%, transparent);
        box-shadow: 0 0 12px #25D366;
        animation: laser-sweep 2.8s ease-in-out infinite;
        z-index: 4;
        pointer-events: none;
    }
    @keyframes laser-sweep {
        0% { top: 12px; opacity: 0; }
        15% { opacity: 1; }
        85% { opacity: 1; }
        100% { top: 250px; opacity: 0; }
    }

    /* ── Live WhatsApp Chat Simulator ── */
    .wa-chat-simulator {
        background-color: #efeae2;
        background-image: radial-gradient(#d1d7db 1px, transparent 1px);
        background-size: 16px 16px;
        border-radius: 16px;
        padding: 20px;
        position: relative;
        min-height: 180px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        border: 1px solid rgba(0,0,0,0.06);
    }
    .wa-bubble-out {
        background: #dcf8c6;
        border-radius: 12px 12px 2px 12px;
        padding: 10px 14px 6px 14px;
        max-width: 90%;
        margin-left: auto;
        box-shadow: 0 1px 2px rgba(0,0,0,0.13);
        position: relative;
        word-wrap: break-word;
        font-size: 13px;
        color: #111b21;
        line-height: 1.45;
    }
    .wa-bubble-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
        font-size: 10.5px;
        color: #667781;
        margin-top: 4px;
        text-align: right;
    }
    .wa-double-check {
        color: var(--wa-tick);
        font-size: 12px;
        letter-spacing: -3px;
        font-weight: 900;
        margin-left: 2px;
    }

    /* ── Template Chips ── */
    .preset-chip {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 600;
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
    }
    .preset-chip:hover {
        background: #e2e8f0;
        color: #0f172a;
        transform: translateY(-1px);
    }
    .preset-chip.active {
        background: #dcfce7;
        color: #166534;
        border-color: #86efac;
    }

    /* ── Send Button ── */
    .btn-wa-hero {
        background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
        border: none;
        color: #ffffff;
        font-weight: 700;
        border-radius: 30px;
        padding: 13px 24px;
        box-shadow: 0 8px 22px rgba(37, 211, 102, 0.35);
        transition: all 0.2s ease;
        letter-spacing: 0.3px;
    }
    .btn-wa-hero:hover {
        background: linear-gradient(135deg, #22c55e 0%, #0f766e 100%);
        box-shadow: 0 10px 28px rgba(37, 211, 102, 0.45);
        color: #ffffff;
        transform: translateY(-1.5px);
    }
    .btn-wa-hero:active {
        transform: translateY(0);
    }
</style>
@endsection

@section('content')
<div class="wa-hub-wrap">

    <!-- ── 1. COMMAND CENTER HERO BANNER ── -->
    <div class="wa-hero-banner">
        <div class="wa-hero-glow"></div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative" style="z-index:2;">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill text-xs px-3 py-1 fw-bold d-inline-flex align-items-center gap-1.5">
                        <span class="radar-dot green"></span> Socket Engine Online
                    </span>
                    <span class="text-white-50 text-xs font-monospace">Port: 8085</span>
                    <span class="text-white-50 text-xs">•</span>
                    <span class="text-white-50 text-xs font-monospace">Baileys v6.7.18</span>
                </div>
                <h3 class="fw-black text-white mb-1 d-flex align-items-center gap-2.5">
                    <i class="fab fa-whatsapp text-success fs-3"></i> WhatsApp Automation Command Center
                </h3>
                <p class="text-white-50 text-xs mb-0" style="max-width:650px;">
                    Central orchestration hub for automated transactional member cards, QR event tickets, and 2FA/verification OTPs.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button id="btnRefreshStatus" class="btn btn-sm btn-outline-light rounded-pill px-3 py-2 text-xs fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="fas fa-sync-alt" id="refreshIcon"></i>
                    <span>Check Status</span>
                </button>
                <a href="{{ route('admin.config.settings') }}#tab-whatsapp" class="btn btn-sm btn-success rounded-pill px-3.5 py-2 text-xs fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
                    <i class="fas fa-sliders-h"></i>
                    <span>Settings &amp; Rules</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ── 2. TRI-CARD TELEMETRY ROW ── -->
    <div class="row g-3 mb-4">
        
        <!-- Telemetry Card 1: Connection State -->
        <div class="col-lg-4 col-md-6">
            <div class="card wa-elevated-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="rounded-3 bg-success-subtle text-success p-2.5" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                                <i class="fab fa-whatsapp fs-5"></i>
                            </div>
                            <div>
                                <span class="text-xs uppercase fw-bold tracking-wider text-muted d-block">Session Matrix</span>
                                <span class="fw-bold text-dark fs-6" id="deviceStatusText">Checking Session...</span>
                            </div>
                        </div>
                        <div id="statusBadgeContainer">
                            <span class="badge bg-light text-secondary border px-2.5 py-1 rounded-pill text-xs fw-bold">Connecting</span>
                        </div>
                    </div>

                    <p class="text-xs text-secondary mb-3 lh-base" id="deviceSubText">
                        Streaming session telemetry from Baileys multi-device daemon...
                    </p>
                </div>

                <div class="pt-3 border-top d-flex align-items-center justify-content-between text-xs text-muted">
                    <span id="lastUpdatedText"><i class="fas fa-clock me-1 text-muted"></i> Live heartbeat</span>
                    <button id="btnLogoutSession" class="btn btn-link text-danger p-0 text-xs text-decoration-none fw-bold d-none">
                        <i class="fas fa-unlink me-1"></i> Disconnect
                    </button>
                </div>
            </div>
        </div>

        <!-- Telemetry Card 2: Transactional Rules Engine -->
        <div class="col-lg-4 col-md-6">
            <div class="card wa-elevated-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="rounded-3 bg-primary-subtle text-primary p-2.5" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-bolt fs-5"></i>
                            </div>
                            <div>
                                <span class="text-xs uppercase fw-bold tracking-wider text-muted d-block">Automation Pipelines</span>
                                <span class="fw-bold text-dark fs-6">4 Active Triggers</span>
                            </div>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill text-xs fw-bold">
                            Parallel Queue
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs text-secondary">
                        <div class="d-flex justify-content-between align-items-center py-0.5">
                            <span><i class="fas fa-id-card text-primary me-2"></i> Member ID Cards</span>
                            <span class="badge bg-light text-success border px-2 py-0.5 fw-bold">Auto-PDF</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-0.5">
                            <span><i class="fas fa-ticket-alt text-warning me-2"></i> Event Ticket Passes</span>
                            <span class="badge bg-light text-success border px-2 py-0.5 fw-bold">Auto-QR</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-0.5">
                            <span><i class="fas fa-key text-info me-2"></i> Booking &amp; Renewal OTPs</span>
                            <span class="badge bg-light text-success border px-2 py-0.5 fw-bold">&lt; 3s Delivery</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-0.5">
                            <span><i class="fas fa-robot text-danger me-2"></i> Telegram Button Approvals</span>
                            <span class="badge bg-light text-success border px-2 py-0.5 fw-bold">Synchronized</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-top text-end">
                    <a href="{{ route('admin.config.settings') }}#tab-whatsapp" class="text-xs text-primary fw-bold text-decoration-none">
                        Manage trigger switches <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Telemetry Card 3: Daemon Health -->
        <div class="col-lg-4 col-md-12">
            <div class="card wa-elevated-card h-100 p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2.5">
                            <div class="rounded-3 bg-purple-subtle text-purple p-2.5" style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;background:#f3e8ff;color:#7e22ce;">
                                <i class="fas fa-server fs-5"></i>
                            </div>
                            <div>
                                <span class="text-xs uppercase fw-bold tracking-wider text-muted d-block">Microservice Health</span>
                                <span class="fw-bold text-dark fs-6">PM2 Node 20 Cluster</span>
                            </div>
                        </div>
                        <span class="badge bg-success text-white px-2.5 py-1 rounded-pill text-xs fw-bold">
                            Live IPC
                        </span>
                    </div>

                    <div class="bg-light p-2.5 rounded-3 mb-2 text-xs">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Target:</span>
                            <span class="font-monospace fw-bold text-dark">{{ $settings['server_url'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Security:</span>
                            <span class="text-dark fw-bold"><i class="fas fa-lock text-success me-1"></i> Bearer Auth</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Fail-Safe:</span>
                            <span class="text-dark fw-bold">Non-blocking + Mail Fallback</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-top d-flex align-items-center justify-content-between text-xs text-muted">
                    <span>Uptime: <strong>100% Operational</strong></span>
                    <span class="badge bg-light text-secondary border px-2 py-0.5">Node v20.19.4</span>
                </div>
            </div>
        </div>

    </div>

    <!-- ── 3. MAIN WORKSTATION ROW (Pairing Hub & Message Studio) ── -->
    <div class="row g-4 align-items-stretch">

        <!-- Column 1: Next-Gen Device Pairing Hub -->
        <div class="col-lg-5 d-flex flex-column">
            <div class="card wa-elevated-card flex-grow-1 p-0 overflow-hidden">
                <div class="card-header bg-white border-bottom border-light p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-black text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fab fa-whatsapp text-success fs-4"></i> Device Pairing Hub
                        </h5>
                        <p class="text-xs text-muted mb-0 mt-0.5">Link your WhatsApp smartphone camera</p>
                    </div>
                    <span id="qrCountdownBadge" class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill text-xs fw-bold">
                        Auto-refresh: <span id="timerVal" class="text-dark">15</span>s
                    </span>
                </div>

                <div class="card-body p-4 d-flex flex-column justify-content-center text-center">

                    <!-- STATE A: CONNECTED (Device Profile View) -->
                    <div id="panelConnected" class="{{ ($status['connected'] ?? false) ? '' : 'd-none' }} py-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle mb-3 shadow-sm" style="width:84px;height:84px;">
                            <i class="fas fa-check-circle fa-3x text-success"></i>
                        </div>
                        <h4 class="fw-black text-dark mb-1">WhatsApp Account Linked!</h4>
                        <p class="text-muted text-xs mx-auto mb-4" style="max-width:340px;">
                            The PMCC-UK engine is authenticated with WhatsApp. Automated messages will dispatch seamlessly from this device.
                        </p>

                        <div class="p-3 bg-light rounded-4 text-start mx-auto mb-4 border" style="max-width:360px;">
                            <div class="d-flex justify-content-between py-1 border-bottom border-light">
                                <span class="text-xs text-muted">Connected User:</span>
                                <span class="text-xs fw-bold text-dark" id="connectedUserName">{{ $status['user']['name'] ?? 'WhatsApp User' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom border-light">
                                <span class="text-xs text-muted">WhatsApp JID:</span>
                                <span class="text-xs font-monospace fw-bold text-dark" id="connectedUserId">{{ $status['user']['id'] ?? 'Authenticated' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-xs text-muted">Socket Channel:</span>
                                <span class="badge bg-success text-xs">Multi-Device Ready</span>
                            </div>
                        </div>

                        <button id="btnUnlinkInside" class="btn btn-outline-danger btn-sm rounded-pill px-4 py-2 text-xs fw-bold">
                            <i class="fas fa-unlink me-1.5"></i> Disconnect / Unlink Device
                        </button>
                    </div>

                    <!-- STATE B: QR SCANNER (Viewfinder Scanner View) -->
                    <div id="panelQr" class="{{ ($status['connected'] ?? false) ? 'd-none' : '' }}">
                        <div class="my-2">
                            <div class="viewfinder-box">
                                <span class="viewfinder-bracket viewfinder-tl"></span>
                                <span class="viewfinder-tr viewfinder-bracket"></span>
                                <span class="viewfinder-bl viewfinder-bracket"></span>
                                <span class="viewfinder-br viewfinder-bracket"></span>
                                <div class="laser-line" id="laserLine" style="display:none;"></div>
                                
                                <div id="qrContainer" class="position-relative d-flex align-items-center justify-content-center" style="width:250px; height:250px;">
                                    <img id="qrImage" src="" alt="WhatsApp QR Code" class="rounded-3" style="width:240px; height:240px; display:none; object-fit:contain;">
                                    <div id="qrSpinner" class="d-flex flex-column align-items-center justify-content-center" style="width:100%; height:100%;">
                                        <div class="spinner-border text-success mb-3" style="width:2.5rem; height:2.5rem;" role="status"></div>
                                        <span class="text-xs fw-bold text-muted">Contacting WhatsApp Daemon...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4 Step Guide -->
                        <div class="bg-light p-3 rounded-4 text-start mx-auto mt-4 border border-light" style="max-width:400px;">
                            <div class="fw-bold text-xs uppercase tracking-wider text-muted mb-2 d-flex align-items-center gap-1.5">
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
                                    <span>Scan this QR code to complete pairing</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Column 2: Interactive Message Studio & WhatsApp Chat Simulator -->
        <div class="col-lg-7 d-flex flex-column">
            <div class="card wa-elevated-card flex-grow-1 p-0 overflow-hidden d-flex flex-column">
                <div class="card-header bg-white border-bottom border-light p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-black text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-paper-plane text-primary fs-5"></i> Live Message Studio &amp; Simulator
                        </h5>
                        <p class="text-xs text-muted mb-0 mt-0.5">Test real-time dispatch with authentic WhatsApp chat preview</p>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 text-xs fw-bold">Live Sandbox</span>
                </div>

                <div class="card-body p-4 d-flex flex-column justify-content-between flex-grow-1">
                    <form id="formSendTest" class="d-flex flex-column h-100 justify-content-between">
                        @csrf

                        <div>
                            <!-- Mobile Input Row -->
                            <div class="mb-3">
                                <label class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1.5">Recipient Mobile Number</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light text-dark fw-bold border-0 text-xs px-3">
                                        🇬🇧 +44
                                    </span>
                                    <input type="text" name="phone" id="testPhone" class="form-control form-control-lg bg-light border-0 text-sm fw-bold" placeholder="07901296858 (or international 91xxx)" required>
                                </div>
                                <span class="text-xs text-muted mt-1 d-block">Supports UK mobile (e.g. 07901296858) or full international format.</span>
                            </div>

                            <!-- Template Preset Chips -->
                            <div class="mb-3">
                                <label class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1.5 d-block">Quick Message Presets</label>
                                <div class="d-flex flex-wrap gap-1.5">
                                    <span class="preset-chip active" data-type="ping">
                                        <i class="fas fa-bolt me-1 text-warning"></i> Test Ping
                                    </span>
                                    <span class="preset-chip" data-type="member">
                                        <i class="fas fa-id-card me-1 text-primary"></i> Member Card Notice
                                    </span>
                                    <span class="preset-chip" data-type="ticket">
                                        <i class="fas fa-ticket-alt me-1 text-success"></i> Ticket Ready
                                    </span>
                                    <span class="preset-chip" data-type="otp">
                                        <i class="fas fa-shield-alt me-1 text-danger"></i> OTP Verification
                                    </span>
                                </div>
                            </div>

                            <!-- Split: Editor & Live WhatsApp Preview -->
                            <div class="row g-3 mb-3">
                                <!-- Textarea Input -->
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-1.5">
                                        <label class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-0">Message Body</label>
                                        <span class="text-xs text-muted" id="charCounter">0 chars</span>
                                    </div>
                                    <textarea name="message" id="testMessage" rows="6" class="form-control bg-light border-0 rounded-3 text-sm p-3" required style="resize:none;">Hello from Plymouth Malayalee Community Club (PMCC-UK)! 🌟

This is a test notification confirming that our WhatsApp automation service is active and operating correctly.

🌐 https://pmccuk.org</textarea>
                                </div>

                                <!-- Real-Time WhatsApp Chat Bubble Mockup -->
                                <div class="col-md-6">
                                    <label class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1.5 d-block">
                                        <i class="fab fa-whatsapp text-success me-1"></i> Live WhatsApp Preview
                                    </label>
                                    <div class="wa-chat-simulator h-100">
                                        <div class="wa-bubble-out shadow-sm">
                                            <div id="simulatedBubbleText" style="white-space:pre-line;">Hello from Plymouth Malayalee Community Club (PMCC-UK)! 🌟

This is a test notification confirming that our WhatsApp automation service is active and operating correctly.

🌐 https://pmccuk.org</div>
                                            <div class="wa-bubble-meta">
                                                <span id="simulatedTime">12:00</span>
                                                <span class="wa-double-check">✓✓</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="testAlert" class="alert d-none mb-3 text-xs rounded-3 p-3"></div>
                        </div>

                        <!-- Dispatch Action -->
                        <div class="pt-2">
                            <button type="submit" id="btnSubmitTest" class="btn btn-wa-hero w-100 py-3 text-sm d-flex align-items-center justify-content-center gap-2">
                                <i class="fab fa-whatsapp fs-5"></i>
                                <span>Send Live WhatsApp Notification</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- ── JAVASCRIPT ENGINE ── -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrImage = document.getElementById('qrImage');
    const qrSpinner = document.getElementById('qrSpinner');
    const laserLine = document.getElementById('laserLine');
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
    const btnLogoutSession = document.getElementById('btnLogoutSession');
    const testMessage = document.getElementById('testMessage');
    const simulatedBubbleText = document.getElementById('simulatedBubbleText');
    const simulatedTime = document.getElementById('simulatedTime');
    const charCounter = document.getElementById('charCounter');
    const presetChips = document.querySelectorAll('.preset-chip');

    let countdown = 15;
    let pollInterval = null;

    // Set Live Simulator Time
    function updateSimTime() {
        const d = new Date();
        if (simulatedTime) {
            simulatedTime.textContent = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    }
    updateSimTime();

    // Sync Textarea with Live WhatsApp Bubble
    function syncChatBubble() {
        if (testMessage && simulatedBubbleText) {
            simulatedBubbleText.textContent = testMessage.value || 'Type your message...';
        }
        if (charCounter && testMessage) {
            charCounter.textContent = `${testMessage.value.length} chars`;
        }
    }
    testMessage?.addEventListener('input', syncChatBubble);
    syncChatBubble();

    // Template Presets
    const presets = {
        ping: `Hello from Plymouth Malayalee Community Club (PMCC-UK)! 🌟\n\nThis is a test notification confirming that our WhatsApp automation service is active and operating correctly.\n\n🌐 https://pmccuk.org`,
        member: `Dear Member,\n\nCongratulations! Your PMCC-UK membership has been approved.\n\n🆔 Membership ID: PMCC-1052\n📅 Valid Until: 31 Dec 2027\n\nYour official digital ID Card is attached for entry and benefits.\n\nWarm regards,\nPMCC-UK Executive Committee`,
        ticket: `🎟️ PMCC-UK Event Ticket Confirmation\n\nDear Member,\nYour booking for 'Onam Celebration 2026' has been approved.\n\nRef: BOOK-1052\nTotal Attendees: 4 (2 Adults, 2 Kids)\n\nPlease present the attached PDF pass with QR code at the entrance desk.\n\nSee you there! 🎉`,
        otp: `PMCC-UK Verification Code: 582910\n\nUse this one-time code to complete your Event Booking verification.\n\nDo not share this code with anyone.\n(Valid for 10 minutes)`
    };

    presetChips.forEach(chip => {
        chip.addEventListener('click', function() {
            presetChips.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const type = this.getAttribute('data-type');
            if (presets[type]) {
                testMessage.value = presets[type];
                syncChatBubble();
            }
        });
    });

    // ── Fetch Status & QR ──
    function fetchQrAndStatus() {
        if (refreshIcon) refreshIcon.classList.add('fa-spin');

        fetch('{{ route('admin.whatsapp.qr') }}')
            .then(res => res.json())
            .then(data => {
                if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                if (lastUpdatedText) {
                    const now = new Date();
                    lastUpdatedText.innerHTML = `<i class="fas fa-clock me-1 text-muted"></i> Checked at ${now.toLocaleTimeString()}`;
                }

                if (data.connected) {
                    // Connected state
                    panelConnected.classList.remove('d-none');
                    panelQr.classList.add('d-none');
                    if (laserLine) laserLine.style.display = 'none';

                    statusBadge.innerHTML = '<span class="badge bg-success text-white border-0 px-3 py-1.5 rounded-pill text-xs fw-bold d-inline-flex align-items-center gap-1.5"><span class="radar-dot green"></span> Connected</span>';
                    statusText.textContent = 'Ready & Active';
                    subText.innerHTML = `Linked WhatsApp account: <strong class="text-dark">${data.user?.name || data.user?.id || 'Authenticated Account'}</strong>`;
                    
                    const userNameEl = document.getElementById('connectedUserName');
                    const userIdEl = document.getElementById('connectedUserId');
                    if (userNameEl) userNameEl.textContent = data.user?.name || 'Authenticated User';
                    if (userIdEl) userIdEl.textContent = data.user?.id || 'Active Session';
                    if (btnLogoutSession) btnLogoutSession.classList.remove('d-none');
                } else if (data.qr) {
                    // Pairing Required / QR Ready
                    panelConnected.classList.add('d-none');
                    panelQr.classList.remove('d-none');
                    qrImage.src = data.qr;
                    qrImage.style.display = 'block';
                    qrSpinner.style.display = 'none';
                    if (laserLine) laserLine.style.display = 'block';

                    statusBadge.innerHTML = '<span class="badge bg-warning text-dark border-0 px-3 py-1.5 rounded-pill text-xs fw-bold d-inline-flex align-items-center gap-1.5"><span class="radar-dot amber"></span> Scan QR Code</span>';
                    statusText.textContent = 'Pairing Required';
                    subText.textContent = 'Multi-Device socket online. Scan the viewfinder QR code on this page.';
                    if (btnLogoutSession) btnLogoutSession.classList.add('d-none');
                } else {
                    // Offline / Connecting
                    panelConnected.classList.add('d-none');
                    panelQr.classList.remove('d-none');
                    qrImage.style.display = 'none';
                    qrSpinner.style.display = 'flex';
                    if (laserLine) laserLine.style.display = 'none';

                    statusBadge.innerHTML = '<span class="badge bg-danger text-white border-0 px-3 py-1.5 rounded-pill text-xs fw-bold d-inline-flex align-items-center gap-1.5"><span class="radar-dot red"></span> Disconnected</span>';
                    statusText.textContent = 'Disconnected';
                    subText.textContent = 'Contacting daemon service at http://127.0.0.1:8085...';
                    if (btnLogoutSession) btnLogoutSession.classList.add('d-none');
                }
            })
            .catch(err => {
                if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                console.warn('[WhatsApp Poll Error]', err);
            });
    }

    // Initial Trigger
    fetchQrAndStatus();

    // Timer Loop (15s)
    pollInterval = setInterval(function() {
        countdown--;
        if (timerVal) timerVal.textContent = countdown;
        if (countdown <= 0) {
            countdown = 15;
            fetchQrAndStatus();
        }
    }, 1000);

    // Refresh Click
    document.getElementById('btnRefreshStatus')?.addEventListener('click', function() {
        countdown = 15;
        fetchQrAndStatus();
    });

    // Unlink Device Action
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

    btnLogoutSession?.addEventListener('click', doLogout);
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
            btnSubmitTest.innerHTML = '<i class="fab fa-whatsapp fs-5"></i> <span>Send Live WhatsApp Notification</span>';

            if (data.success) {
                testAlert.className = 'alert alert-success mb-3 text-xs border-0 bg-success-subtle text-success p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm';
                testAlert.innerHTML = `<i class="fas fa-check-circle fs-5"></i> <div><strong>Success!</strong> ${data.message}</div>`;
            } else {
                testAlert.className = 'alert alert-danger mb-3 text-xs border-0 bg-danger-subtle text-danger p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm';
                testAlert.innerHTML = `<i class="fas fa-exclamation-circle fs-5"></i> <div><strong>Failed:</strong> ${data.message || 'Could not dispatch message.'}</div>`;
            }
        })
        .catch(err => {
            btnSubmitTest.disabled = false;
            btnSubmitTest.innerHTML = '<i class="fab fa-whatsapp fs-5"></i> <span>Send Live WhatsApp Notification</span>';
            testAlert.className = 'alert alert-danger mb-3 text-xs border-0 bg-danger-subtle text-danger p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm';
            testAlert.innerHTML = `<i class="fas fa-wifi fs-5"></i> <div><strong>Network Error:</strong> ${err}</div>`;
        });
    });
});
</script>
@endsection
