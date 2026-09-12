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
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 9px rgba(34, 197, 94, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
            }
        }

        /* ── High-Tech Viewfinder QR Box ── */
        .viewfinder-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 8px 0;
            width: 100%;
        }

        .viewfinder-box {
            position: relative;
            background: #ffffff;
            width: 276px;
            height: 276px;
            padding: 16px;
            border-radius: 26px;
            box-shadow: 0 16px 40px -10px rgba(37, 211, 102, 0.18), 0 0 0 1px rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 0 auto;
        }

        .viewfinder-bracket {
            position: absolute;
            width: 28px;
            height: 28px;
            border-color: #25D366;
            border-style: solid;
            pointer-events: none;
            z-index: 5;
        }

        .viewfinder-tl {
            top: 10px;
            left: 10px;
            border-width: 4px 0 0 4px;
            border-radius: 10px 0 0 0;
        }

        .viewfinder-tr {
            top: 10px;
            right: 10px;
            border-width: 4px 4px 0 0;
            border-radius: 0 10px 0 0;
        }

        .viewfinder-bl {
            bottom: 10px;
            left: 10px;
            border-width: 0 0 4px 4px;
            border-radius: 0 0 0 10px;
        }

        .viewfinder-br {
            bottom: 10px;
            right: 10px;
            border-width: 0 4px 4px 0;
            border-radius: 0 0 10px 0;
        }

        /* Laser Scanning Line */
        .laser-line {
            position: absolute;
            left: 16px;
            right: 16px;
            height: 2.5px;
            background: linear-gradient(90deg, transparent, #25D366 50%, transparent);
            box-shadow: 0 0 14px 2px rgba(37, 211, 102, 0.7);
            animation: laser-sweep 2.8s ease-in-out infinite;
            z-index: 4;
            pointer-events: none;
        }

        @keyframes laser-sweep {
            0% {
                top: 14px;
                opacity: 0;
            }

            15% {
                opacity: 1;
            }

            85% {
                opacity: 1;
            }

            100% {
                top: 258px;
                opacity: 0;
            }
        }

        /* ── Redefined Pairing Indication ── */
        .pairing-guide-card {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1px solid rgba(0, 0, 0, 0.07);
            border-radius: 20px;
            padding: 16px 18px;
            margin: 18px auto 0;
            max-width: 420px;
            box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.03);
            text-align: left;
        }

        .pairing-step-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 10px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.04);
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }

        .pairing-step-row:last-child {
            margin-bottom: 0;
        }

        .pairing-step-row:hover {
            border-color: rgba(37, 211, 102, 0.4);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            transform: translateY(-1px);
        }

        .pairing-step-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .pairing-step-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }

        .pairing-step-text {
            flex-grow: 1;
            line-height: 1.35;
        }

        .pairing-step-title {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1px;
        }

        .pairing-step-desc {
            font-size: 11px;
            color: #64748b;
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
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .wa-bubble-out {
            background: #dcf8c6;
            border-radius: 12px 12px 2px 12px;
            padding: 10px 14px 6px 14px;
            max-width: 90%;
            margin-left: auto;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.13);
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

        /* ── Terminal Log Console Styling ── */
        .terminal-window {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 18px;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }

        .terminal-header {
            background: #161b22;
            border-bottom: 1px solid #30363d;
            padding: 14px 20px;
        }

        .terminal-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            display: inline-block;
        }

        .terminal-dot.red {
            background: #ff5f56;
        }

        .terminal-dot.yellow {
            background: #ffbd2e;
        }

        .terminal-dot.green {
            background: #27c93f;
        }

        .terminal-body {
            background: #0b0f19;
            color: #e6edf3;
            font-family: 'JetBrains Mono', 'SF Mono', Menlo, Consolas, 'Liberation Mono', monospace;
            font-size: 12px;
            line-height: 1.6;
            height: 400px;
            overflow-y: auto;
            padding: 14px 18px;
        }

        .terminal-body::-webkit-scrollbar {
            width: 8px;
        }

        .terminal-body::-webkit-scrollbar-track {
            background: #0d1117;
        }

        .terminal-body::-webkit-scrollbar-thumb {
            background: #30363d;
            border-radius: 4px;
        }

        .terminal-body::-webkit-scrollbar-thumb:hover {
            background: #484f58;
        }

        .log-entry {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 4px 8px;
            border-radius: 5px;
            transition: background-color 0.15s;
        }

        .log-entry:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .log-ts {
            color: #8b949e;
            flex-shrink: 0;
            user-select: none;
            font-size: 11px;
        }

        .log-badge-pill {
            font-size: 9.5px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            flex-shrink: 0;
        }

        .log-badge-inbound {
            background: rgba(56, 189, 248, 0.2);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .log-badge-outbound {
            background: rgba(52, 211, 153, 0.2);
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.3);
        }

        .log-badge-system {
            background: rgba(168, 85, 247, 0.2);
            color: #c084fc;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }

        .log-badge-qr {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .log-badge-auth {
            background: rgba(244, 63, 94, 0.2);
            color: #fb7185;
            border: 1px solid rgba(244, 63, 94, 0.3);
        }

        .log-badge-error {
            background: rgba(239, 68, 68, 0.25);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        .log-badge-warn {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .log-msg-text {
            word-break: break-word;
            flex-grow: 1;
            color: #e6edf3;
        }

        .log-filter-btn {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 11px;
            border-radius: 20px;
            border: 1px solid #30363d;
            background: #161b22;
            color: #8b949e;
            transition: all 0.2s;
            cursor: pointer;
        }

        .log-filter-btn:hover {
            color: #e6edf3;
            border-color: #484f58;
        }

        .log-filter-btn.active {
            background: #238636;
            color: #ffffff;
            border-color: #2ea043;
        }

        .pulse-dot {
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.3;
                transform: scale(0.85);
            }
        }
    </style>
@endsection

@section('content')
    <div class="wa-hub-wrap">

        <!-- ── 1. COMMAND CENTER HERO BANNER ── -->
        <div class="wa-hero-banner">
            <div class="wa-hero-glow"></div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative"
                style="z-index:2;">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span
                            class="badge bg-success-subtle text-success border border-success-subtle rounded-pill text-xs px-3 py-1 fw-bold d-inline-flex align-items-center gap-1.5">
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
                        Central orchestration hub for automated transactional member cards, QR event tickets, and
                        2FA/verification OTPs.
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                        class="btn btn-sm btn-light text-dark rounded-pill px-3.5 py-2 text-xs fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm"
                        data-bs-toggle="modal" data-bs-target="#modalAdminTestMessage">
                        <i class="fas fa-paper-plane text-success"></i>
                        <span>Send Test Message</span>
                    </button>
                    <button id="btnRefreshStatus"
                        class="btn btn-sm btn-outline-light rounded-pill px-3 py-2 text-xs fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
                        <i class="fas fa-sync-alt" id="refreshIcon"></i>
                        <span>Check Status</span>
                    </button>
                    <a href="{{ route('admin.config.settings') }}#tab-whatsapp"
                        class="btn btn-sm btn-success rounded-pill px-3.5 py-2 text-xs fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm">
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
                                <div class="rounded-3 bg-success-subtle text-success p-2.5"
                                    style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fab fa-whatsapp fs-5"></i>
                                </div>
                                <div>
                                    <span class="text-xs uppercase fw-bold tracking-wider text-muted d-block">Session
                                        Matrix</span>
                                    <span class="fw-bold text-dark fs-6" id="deviceStatusText">Checking Session...</span>
                                </div>
                            </div>
                            <div id="statusBadgeContainer">
                                <span
                                    class="badge bg-light text-secondary border px-2.5 py-1 rounded-pill text-xs fw-bold">Connecting</span>
                            </div>
                        </div>

                        <p class="text-xs text-secondary mb-3 lh-base" id="deviceSubText">
                            Streaming session telemetry from Baileys multi-device daemon...
                        </p>
                    </div>

                    <div class="pt-3 border-top d-flex align-items-center justify-content-between text-xs text-muted">
                        <span id="lastUpdatedText"><i class="fas fa-clock me-1 text-muted"></i> Live heartbeat</span>
                        <button type="button"
                            class="btn btn-outline-danger btn-xs px-2.5 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-1 shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#modalRevokeSession" id="btnTelemetryRevoke">
                            <i class="fas fa-shield-alt"></i> Revoke Options
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
                                <div class="rounded-3 bg-primary-subtle text-primary p-2.5"
                                    style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-bolt fs-5"></i>
                                </div>
                                <div>
                                    <span class="text-xs uppercase fw-bold tracking-wider text-muted d-block">Automation
                                        Pipelines</span>
                                    <span class="fw-bold text-dark fs-6">4 Active Triggers</span>
                                </div>
                            </div>
                            <span
                                class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill text-xs fw-bold">
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
                                <span><i class="fas fa-shield-alt text-danger me-2"></i> Telegram &amp; WhatsApp Admin
                                    Alerts</span>
                                <span class="badge bg-light text-success border px-2 py-0.5 fw-bold">Live Mirrored</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <button type="button" id="btnTestAdminAlertFromWaHub"
                            class="btn btn-outline-dark btn-xs rounded-pill px-2.5 py-1 text-xxs fw-bold d-inline-flex align-items-center gap-1 shadow-sm">
                            <i class="fas fa-bell text-warning"></i>
                            <span>Test Admin Alert</span>
                        </button>
                        <a href="{{ route('admin.config.settings') }}#tab-whatsapp"
                            class="text-xs text-primary fw-bold text-decoration-none">
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
                                <div class="rounded-3 bg-purple-subtle text-purple p-2.5"
                                    style="width:44px;height:44px;display:flex;align-items:center;justify-content:center;background:#f3e8ff;color:#7e22ce;">
                                    <i class="fas fa-server fs-5"></i>
                                </div>
                                <div>
                                    <span class="text-xs uppercase fw-bold tracking-wider text-muted d-block">Microservice
                                        Health</span>
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
                                <span class="text-dark fw-bold"><i class="fas fa-lock text-success me-1"></i> Bearer
                                    Auth</span>
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
                    <div
                        class="card-header bg-white border-bottom border-light p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-black text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="fab fa-whatsapp text-success fs-4"></i> Device Pairing Hub
                            </h5>
                            <p class="text-xs text-muted mb-0 mt-0.5">Link your WhatsApp smartphone camera</p>
                        </div>
                        <span id="qrCountdownBadge"
                            class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill text-xs fw-bold">
                            Auto-refresh: <span id="timerVal" class="text-dark">15</span>s
                        </span>
                    </div>

                    <div class="card-body p-4 d-flex flex-column justify-content-center text-center">

                        <!-- STATE A: CONNECTED (Device Profile View) -->
                        <div id="panelConnected" class="{{ ($status['connected'] ?? false) ? '' : 'd-none' }} py-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle mb-3 shadow-sm"
                                style="width:84px;height:84px;">
                                <i class="fas fa-check-circle fa-3x text-success"></i>
                            </div>
                            <h4 class="fw-black text-dark mb-1">WhatsApp Account Linked!</h4>
                            <p class="text-muted text-xs mx-auto mb-4" style="max-width:340px;">
                                The PMCC-UK engine is authenticated with WhatsApp. Automated messages will dispatch
                                seamlessly from this device.
                            </p>

                            <div class="p-3 bg-light rounded-4 text-start mx-auto mb-4 border" style="max-width:360px;">
                                <div class="d-flex justify-content-between py-1 border-bottom border-light">
                                    <span class="text-xs text-muted">Connected User:</span>
                                    <span class="text-xs fw-bold text-dark"
                                        id="connectedUserName">{{ $status['user']['name'] ?? 'WhatsApp User' }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1 border-bottom border-light">
                                    <span class="text-xs text-muted">WhatsApp JID:</span>
                                    <span class="text-xs font-monospace fw-bold text-dark"
                                        id="connectedUserId">{{ $status['user']['id'] ?? 'Authenticated' }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1">
                                    <span class="text-xs text-muted">Socket Channel:</span>
                                    <span class="badge bg-success text-xs">Multi-Device Ready</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-center gap-2">
                                <button type="button"
                                    class="btn btn-outline-danger btn-sm rounded-pill px-4 py-2 text-xs fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm"
                                    data-bs-toggle="modal" data-bs-target="#modalRevokeSession">
                                    <i class="fas fa-shield-alt"></i> Revoke &amp; Unlink Session
                                </button>
                            </div>
                        </div>

                        <!-- STATE B: QR SCANNER (Viewfinder Scanner View) -->
                        <div id="panelQr" class="{{ ($status['connected'] ?? false) ? 'd-none' : '' }}">
                            <div class="viewfinder-wrapper my-2">
                                <div class="viewfinder-box">
                                    <span class="viewfinder-bracket viewfinder-tl"></span>
                                    <span class="viewfinder-tr viewfinder-bracket"></span>
                                    <span class="viewfinder-bl viewfinder-bracket"></span>
                                    <span class="viewfinder-br viewfinder-bracket"></span>
                                    <div class="laser-line" id="laserLine" style="display:none;"></div>

                                    <div id="qrContainer"
                                        style="width: 244px; height: 244px; position: relative; display: flex; align-items: center; justify-content: center; margin: 0 auto; overflow: hidden; border-radius: 14px; background: #ffffff;">
                                        <img id="qrImage" src="" alt="WhatsApp QR Code" class="rounded-3"
                                            style="width: 240px; height: 240px; display: none; object-fit: contain; margin: auto;">
                                        <div id="qrSpinner"
                                            style="position: absolute; inset: 0; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #ffffff; z-index: 2; border-radius: 14px;">
                                            <div class="spinner-border text-success mb-2"
                                                style="width: 2.4rem; height: 2.4rem;" role="status"></div>
                                            <span class="text-xs fw-bold text-dark">Generating fresh QR code...</span>
                                            <span class="text-xxs text-muted mt-1">Multi-Device socket ready</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Redefined Pairing Indication Stepper Card ── -->
                            <div class="pairing-guide-card">
                                <div class="d-flex justify-content-between align-items-center mb-2.5 pb-2 border-bottom border-light">
                                    <div class="d-flex align-items-center gap-1.5">
                                        <span class="radar-dot green"></span>
                                        <span class="fw-bold text-xs uppercase tracking-wider text-dark">Device Pairing Flow</span>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 rounded-pill text-xxs fw-bold">
                                        <i class="fas fa-lock me-1"></i> Multi-Device v2 E2EE
                                    </span>
                                </div>

                                <div class="pairing-step-row">
                                    <div class="pairing-step-num">1</div>
                                    <div class="pairing-step-icon bg-success-subtle text-success">
                                        <i class="fab fa-whatsapp"></i>
                                    </div>
                                    <div class="pairing-step-text">
                                        <div class="pairing-step-title">Open WhatsApp</div>
                                        <div class="pairing-step-desc">Launch WhatsApp application on your smartphone</div>
                                    </div>
                                </div>

                                <div class="pairing-step-row">
                                    <div class="pairing-step-num">2</div>
                                    <div class="pairing-step-icon bg-primary-subtle text-primary">
                                        <i class="fas fa-mobile-screen"></i>
                                    </div>
                                    <div class="pairing-step-text">
                                        <div class="pairing-step-title">Navigate to Linked Devices</div>
                                        <div class="pairing-step-desc">Tap <strong>Settings ⚙️</strong> (iPhone) or <strong>Menu ⋮</strong> (Android) &gt; <strong>Linked Devices</strong></div>
                                    </div>
                                </div>

                                <div class="pairing-step-row">
                                    <div class="pairing-step-num">3</div>
                                    <div class="pairing-step-icon bg-warning-subtle text-warning">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                    <div class="pairing-step-text">
                                        <div class="pairing-step-title">Tap "Link a Device"</div>
                                        <div class="pairing-step-desc">Authenticate with Face ID, Fingerprint, or PIN</div>
                                    </div>
                                </div>

                                <div class="pairing-step-row">
                                    <div class="pairing-step-num">4</div>
                                    <div class="pairing-step-icon bg-info-subtle text-info">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                    <div class="pairing-step-text">
                                        <div class="pairing-step-title">Scan Viewfinder Frame</div>
                                        <div class="pairing-step-desc">Point camera directly at the green bracketed QR code above</div>
                                    </div>
                                </div>

                                <div class="mt-2.5 pt-2 border-top border-light d-flex justify-content-between align-items-center">
                                    <span class="text-xxs text-muted d-flex align-items-center gap-1">
                                        <i class="fas fa-bolt text-success"></i> Instant auto-connect upon scan
                                    </span>
                                    <button type="button" class="btn btn-link p-0 text-danger text-xxs text-decoration-none fw-semibold"
                                        onclick="openRevokeModal(true)">
                                        <i class="fas fa-redo-alt me-0.5"></i> Force Reset Gateway
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Column 2: Interactive Message Studio & WhatsApp Chat Simulator -->
            <div class="col-lg-7 d-flex flex-column">
                <div class="card wa-elevated-card flex-grow-1 p-0 overflow-hidden d-flex flex-column">
                    <div
                        class="card-header bg-white border-bottom border-light p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-black text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="fas fa-paper-plane text-primary fs-5"></i> Live Message Studio &amp; Simulator
                            </h5>
                            <p class="text-xs text-muted mb-0 mt-0.5">Test real-time dispatch with authentic WhatsApp chat
                                preview</p>
                        </div>
                        <span
                            class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 text-xs fw-bold">Live
                            Sandbox</span>
                    </div>

                    <div class="card-body p-4 d-flex flex-column justify-content-between flex-grow-1">
                        <form id="formSendTest" class="d-flex flex-column h-100 justify-content-between">
                            @csrf

                            <div>
                                <!-- Mobile Input Row -->
                                <div class="mb-3">
                                    <label
                                        class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1.5">Recipient
                                        Mobile Number</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-light text-dark fw-bold border-0 text-xs px-3">
                                            🇬🇧 +44
                                        </span>
                                        <input type="text" name="phone" id="testPhone"
                                            class="form-control form-control-lg bg-light border-0 text-sm fw-bold"
                                            placeholder="07901296858 (or international 91xxx)" required>
                                    </div>
                                    <span class="text-xs text-muted mt-1 d-block">Supports UK mobile (e.g. 07901296858) or
                                        full international format.</span>
                                </div>

                                <!-- Template Preset Chips -->
                                <div class="mb-3">
                                    <label
                                        class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1.5 d-block">Quick
                                        Message Presets</label>
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
                                            <label
                                                class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-0">Message
                                                Body</label>
                                            <span class="text-xs text-muted" id="charCounter">0 chars</span>
                                        </div>
                                        <textarea name="message" id="testMessage" rows="6"
                                            class="form-control bg-light border-0 rounded-3 text-sm p-3" required
                                            style="resize:none;">Hello from Plymouth Malayalee Community Club (PMCC-UK)! 🌟

    This is a test notification confirming that our WhatsApp automation service is active and operating correctly.

    🌐 https://pmccuk.org</textarea>
                                    </div>

                                    <!-- Real-Time WhatsApp Chat Bubble Mockup -->
                                    <div class="col-md-6">
                                        <label
                                            class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1.5 d-block">
                                            <i class="fab fa-whatsapp text-success me-1"></i> Live WhatsApp Preview
                                        </label>
                                        <div class="wa-chat-simulator h-100">
                                            <div class="wa-bubble-out shadow-sm">
                                                <div id="simulatedBubbleText" style="white-space:pre-line;">Hello from
                                                    Plymouth Malayalee Community Club (PMCC-UK)! 🌟

                                                    This is a test notification confirming that our WhatsApp automation
                                                    service is active and operating correctly.

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
                                <button type="submit" id="btnSubmitTest"
                                    class="btn btn-wa-hero w-100 py-3 text-sm d-flex align-items-center justify-content-center gap-2">
                                    <i class="fab fa-whatsapp fs-5"></i>
                                    <span>Send Live WhatsApp Notification</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── 4. BROADCAST STUDIO & 2-WAY INTERACTIVE BOT HUB ── -->
        <div class="row g-4 mb-4">

            <!-- Column 1: Targeted Community Broadcast Studio -->
            <div class="col-lg-7">
                <div class="card wa-elevated-card h-100 p-0 overflow-hidden d-flex flex-column">
                    <div
                        class="card-header bg-white border-bottom border-light p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-black text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="fas fa-bullhorn text-warning fs-5"></i> Targeted Community Broadcast Studio
                            </h5>
                            <p class="text-xs text-muted mb-0 mt-0.5">Send festival greetings, urgent weather alerts, or
                                student notices to curated segments</p>
                        </div>
                        <span
                            class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1 text-xs fw-bold">Mass
                            Messaging</span>
                    </div>

                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <form id="formBroadcast" class="d-flex flex-column gap-3">
                            @csrf

                            <!-- Audience Segment Selector -->
                            <div>
                                <label
                                    class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-2 d-block">1.
                                    Select Target Audience</label>
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <label
                                            class="p-3 border rounded-3 d-flex align-items-center justify-content-between cursor-pointer w-100 bg-light-subtle audience-option"
                                            style="cursor:pointer;">
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="radio" name="broadcast_audience" value="members" checked
                                                    class="form-check-input mt-0">
                                                <span class="text-xs fw-bold text-dark">Active Members</span>
                                            </div>
                                            <span
                                                class="badge bg-success-subtle text-success rounded-pill text-xs px-2">{{ $counts['members'] ?? 0 }}</span>
                                        </label>
                                    </div>
                                    <div class="col-sm-6">
                                        <label
                                            class="p-3 border rounded-3 d-flex align-items-center justify-content-between cursor-pointer w-100 bg-light-subtle audience-option"
                                            style="cursor:pointer;">
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="radio" name="broadcast_audience" value="attendees"
                                                    class="form-check-input mt-0">
                                                <span class="text-xs fw-bold text-dark">Event Attendees</span>
                                            </div>
                                            <span
                                                class="badge bg-primary-subtle text-primary rounded-pill text-xs px-2">{{ $counts['attendees'] ?? 0 }}</span>
                                        </label>
                                    </div>
                                    <div class="col-sm-6">
                                        <label
                                            class="p-3 border rounded-3 d-flex align-items-center justify-content-between cursor-pointer w-100 bg-light-subtle audience-option"
                                            style="cursor:pointer;">
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="radio" name="broadcast_audience" value="students"
                                                    class="form-check-input mt-0">
                                                <span class="text-xs fw-bold text-dark">University Students</span>
                                            </div>
                                            <span
                                                class="badge bg-info-subtle text-info rounded-pill text-xs px-2">{{ $counts['students'] ?? 0 }}</span>
                                        </label>
                                    </div>
                                    <div class="col-sm-6">
                                        <label
                                            class="p-3 border rounded-3 d-flex align-items-center justify-content-between cursor-pointer w-100 bg-light-subtle audience-option"
                                            style="cursor:pointer;">
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="radio" name="broadcast_audience" value="custom"
                                                    class="form-check-input mt-0">
                                                <span class="text-xs fw-bold text-dark">Custom Numbers</span>
                                            </div>
                                            <span
                                                class="badge bg-secondary-subtle text-secondary rounded-pill text-xs px-2">Manual</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Custom Numbers Textarea (Hidden by default) -->
                                <div id="customNumbersWrap" class="mt-2.5 d-none">
                                    <label class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1">Enter
                                        Phone Numbers (one per line or comma-separated)</label>
                                    <textarea name="custom_numbers" id="broadcastCustomNumbers" rows="2"
                                        class="form-control bg-light border-0 text-xs font-monospace"
                                        placeholder="07901296858, 447812345678, 919876543210"></textarea>
                                </div>
                            </div>

                            <!-- Broadcast Templates -->
                            <div>
                                <label
                                    class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1.5 d-block">2.
                                    Choose Template or Customize</label>
                                <div class="d-flex flex-wrap gap-1.5 mb-2">
                                    <span class="preset-chip active bcast-chip" data-bcast="onam">
                                        🌸 Onam &amp; Vishu
                                    </span>
                                    <span class="preset-chip bcast-chip" data-bcast="xmas">
                                        🎄 Christmas &amp; New Year
                                    </span>
                                    <span class="preset-chip bcast-chip" data-bcast="weather">
                                        ⚠️ Urgent Weather Alert
                                    </span>
                                    <span class="preset-chip bcast-chip" data-bcast="student">
                                        🎓 Student Wing Welcome
                                    </span>
                                </div>

                                <textarea name="broadcast_message" id="broadcastMessage" rows="5"
                                    class="form-control bg-light border-0 rounded-3 text-xs p-3" required
                                    style="resize:none;">🌸 Warmest Onam Greetings from PMCC-UK! May this festive season bring immense joy, health, and prosperity to you and your family. Join our grand celebrations: https://pmccuk.org/events</textarea>
                                <span class="text-xs text-muted mt-1 d-block">💡 Variable <code>{name}</code> is
                                    automatically replaced with the recipient's name.</span>
                            </div>

                            <!-- Progress Alert -->
                            <div id="broadcastAlert" class="alert d-none text-xs rounded-3 p-3 mb-0"></div>

                            <!-- Dispatch Button -->
                            <div class="pt-1">
                                <button type="submit" id="btnSubmitBroadcast"
                                    class="btn btn-dark w-100 py-2.5 text-xs fw-bold rounded-pill d-flex align-items-center justify-content-center gap-2 shadow-sm">
                                    <i class="fas fa-paper-plane text-success"></i>
                                    <span>Launch Safe Community Broadcast</span>
                                </button>
                                <p class="text-center text-muted mb-0 mt-2" style="font-size:11px;">
                                    <i class="fas fa-shield-alt text-success me-1"></i> Equipped with an anti-spam delay
                                    timer between dispatches to comply with WhatsApp Fair Use standards.
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Column 2: Interactive 2-Way Bot Command Center -->
            <div class="col-lg-5">
                <div class="card wa-elevated-card h-100 p-0 overflow-hidden d-flex flex-column">
                    <div
                        class="card-header bg-white border-bottom border-light p-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-black text-dark mb-0 d-flex align-items-center gap-2">
                                <i class="fas fa-robot text-info fs-5"></i> 2-Way Interactive Bot Hub
                            </h5>
                            <p class="text-xs text-muted mb-0 mt-0.5">Members can text keywords directly to this WhatsApp
                                number</p>
                        </div>
                        <span
                            class="badge bg-info-subtle text-info border border-info-subtle px-2.5 py-1 text-xs fw-bold">Socket
                            Listener</span>
                    </div>

                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div class="d-flex flex-column gap-2.5">

                            <!-- Keyword 1: CARD -->
                            <div class="p-3 rounded-3 border border-light-subtle bg-light d-flex align-items-start gap-3">
                                <div class="badge bg-primary text-white rounded-circle p-2 mt-0.5"
                                    style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-id-card"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-0.5">
                                        <span class="fw-bold text-dark text-xs font-monospace">"CARD" or "ID"</span>
                                        <span class="badge bg-success-subtle text-success text-xxs font-monospace">PDF
                                            Card</span>
                                    </div>
                                    <p class="text-muted text-xs mb-0">Searches member database by phone and instantly
                                        returns the official digital ID card PDF.</p>
                                </div>
                            </div>

                            <!-- Keyword 2: TICKET -->
                            <div class="p-3 rounded-3 border border-light-subtle bg-light d-flex align-items-start gap-3">
                                <div class="badge bg-success text-white rounded-circle p-2 mt-0.5"
                                    style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-0.5">
                                        <span class="fw-bold text-dark text-xs font-monospace">"TICKET" or "PASS"</span>
                                        <span class="badge bg-success-subtle text-success text-xxs font-monospace">QR
                                            Ticket</span>
                                    </div>
                                    <p class="text-muted text-xs mb-0">Retrieves attendee's confirmed booking and sends the
                                        official ticket pass with admission QR.</p>
                                </div>
                            </div>

                            <!-- Keyword 3: EVENTS -->
                            <div class="p-3 rounded-3 border border-light-subtle bg-light d-flex align-items-start gap-3">
                                <div class="badge bg-warning text-dark rounded-circle p-2 mt-0.5"
                                    style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-0.5">
                                        <span class="fw-bold text-dark text-xs font-monospace">"EVENTS"</span>
                                        <span
                                            class="badge bg-warning-subtle text-warning text-xxs font-monospace">Upcoming</span>
                                    </div>
                                    <p class="text-muted text-xs mb-0">Lists all scheduled cultural events, dates, venue
                                        locations, and 1-click booking links.</p>
                                </div>
                            </div>

                            <!-- Keyword 4: OFFERS -->
                            <div class="p-3 rounded-3 border border-light-subtle bg-light d-flex align-items-start gap-3">
                                <div class="badge bg-danger text-white rounded-circle p-2 mt-0.5"
                                    style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-0.5">
                                        <span class="fw-bold text-dark text-xs font-monospace">"OFFERS" / "SPONSORS"</span>
                                        <span
                                            class="badge bg-danger-subtle text-danger text-xxs font-monospace">Discounts</span>
                                    </div>
                                    <p class="text-muted text-xs mb-0">Displays exclusive member discount codes from
                                        Plymouth partner restaurants and shops.</p>
                                </div>
                            </div>

                            <!-- Keyword 5: STUDENT -->
                            <div class="p-3 rounded-3 border border-light-subtle bg-light d-flex align-items-start gap-3">
                                <div class="badge bg-info text-white rounded-circle p-2 mt-0.5"
                                    style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-0.5">
                                        <span class="fw-bold text-dark text-xs font-monospace">"STUDENT"</span>
                                        <span
                                            class="badge bg-info-subtle text-info text-xxs font-monospace">Orientation</span>
                                    </div>
                                    <p class="text-muted text-xs mb-0">Delivers the student onboarding pack: NHS
                                        registration, NI guidance, and WhatsApp group link.</p>
                                </div>
                            </div>

                        </div>

                        <div class="mt-3 p-3 bg-light rounded-4 border border-light text-center">
                            <span class="text-xs text-muted d-block mb-1 font-monospace">Incoming Webhook Endpoint:</span>
                            <code
                                class="text-dark fw-bold text-xs bg-white px-2 py-1 rounded border">POST /api/whatsapp/webhook</code>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── 4. LIVE WHATSAPP GATEWAY LOGS & BOT CONSOLE ── -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="terminal-window">
                    <!-- Terminal Header -->
                    <div class="terminal-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center gap-1.5 me-2">
                                <span class="terminal-dot red"></span>
                                <span class="terminal-dot yellow"></span>
                                <span class="terminal-dot green"></span>
                            </div>
                            <i class="fas fa-terminal text-success"></i>
                            <span class="fw-bold text-white text-xs font-monospace">WhatsApp Gateway &amp; Bot Live Stream
                                Console</span>
                            <span id="logLiveBadge"
                                class="badge bg-success-subtle text-success border border-success-subtle rounded-pill text-xxs px-2 py-0.5 font-monospace">
                                <i class="fas fa-circle text-success me-1 pulse-dot"></i> <span id="logPollStatusText">LIVE
                                    POLLING</span>
                            </span>
                            <span id="logTotalBadge"
                                class="badge bg-dark text-muted border border-secondary text-xxs font-monospace">0
                                events</span>
                        </div>

                        <!-- Controls & Filters Toolbar -->
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <!-- Filter Pills -->
                            <div class="d-flex align-items-center gap-1" id="logFilterPills">
                                <button type="button" class="log-filter-btn active" data-filter="all">All</button>
                                <button type="button" class="log-filter-btn" data-filter="bot">Bot Messages</button>
                                <button type="button" class="log-filter-btn" data-filter="outbound">Dispatches</button>
                                <button type="button" class="log-filter-btn" data-filter="error">Errors &amp;
                                    Warnings</button>
                                <button type="button" class="log-filter-btn" data-filter="system">System</button>
                            </div>

                            <!-- Search Box -->
                            <div class="position-relative" style="width:160px;">
                                <input type="text" id="logSearchInput" placeholder="Filter console..."
                                    class="form-control form-control-sm text-light bg-dark border-secondary text-xs font-monospace py-1 ps-2 pe-4"
                                    style="height:28px;">
                                <i class="fas fa-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"
                                    style="font-size:10px;"></i>
                            </div>

                            <!-- Auto Scroll Toggle -->
                            <div
                                class="form-check form-switch mb-0 d-flex align-items-center gap-1.5 text-xs text-secondary font-monospace">
                                <input class="form-check-input mt-0 cursor-pointer" type="checkbox" id="chkAutoScroll"
                                    checked style="cursor:pointer;">
                                <label class="form-check-label cursor-pointer text-xxs text-light"
                                    for="chkAutoScroll">Scroll</label>
                            </div>

                            <!-- Auto Poll Toggle -->
                            <div
                                class="form-check form-switch mb-0 d-flex align-items-center gap-1.5 text-xs text-secondary font-monospace">
                                <input class="form-check-input mt-0 cursor-pointer" type="checkbox" id="chkAutoPoll" checked
                                    style="cursor:pointer;">
                                <label class="form-check-label cursor-pointer text-xxs text-light"
                                    for="chkAutoPoll">Live</label>
                            </div>

                            <!-- Action Buttons -->
                            <button type="button" id="btnRefreshLogs"
                                class="btn btn-outline-secondary btn-sm p-1 px-2 text-xxs text-light"
                                title="Refresh Logs Now">
                                <i class="fas fa-sync-alt" id="iconRefreshLogs"></i>
                            </button>
                            <button type="button" id="btnClearLogs" class="btn btn-outline-danger btn-sm p-1 px-2 text-xxs"
                                title="Clear Console Buffer">
                                <i class="fas fa-trash-alt"></i> Clear
                            </button>
                            <button type="button" id="btnDownloadLogs"
                                class="btn btn-outline-success btn-sm p-1 px-2 text-xxs" title="Export Logs to File">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>

                    <!-- Terminal Body -->
                    <div class="terminal-body" id="terminalBody">
                        <div
                            class="text-muted text-xxs pb-2 border-bottom border-dark d-flex justify-content-between font-monospace">
                            <span>PMCC-UK WhatsApp Daemon v1.0.0 (Baileys Multi-Device) // Target: 127.0.0.1:8085</span>
                            <span id="bufferStatusText">Displaying live log stream</span>
                        </div>
                        <div id="logLinesContainer" class="pt-2 d-flex flex-column gap-1">
                            <div class="text-center text-muted py-5">
                                <div class="spinner-border spinner-border-sm text-secondary mb-2" role="status"></div>
                                <div class="text-xs">Connecting to WhatsApp Daemon log stream...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ── 4. ADMIN TEST MESSAGE MODAL ── -->
    <div class="modal fade" id="modalAdminTestMessage" tabindex="-1" aria-labelledby="modalAdminTestMessageLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-3.5 border-0">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="rounded-circle bg-success text-white p-2"
                            style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">
                            <i class="fab fa-whatsapp fs-5"></i>
                        </div>
                        <div>
                            <h6 class="modal-title fw-black text-white mb-0" id="modalAdminTestMessageLabel">Send WhatsApp
                                Admin Test Message</h6>
                            <span class="text-white-50" style="font-size:11px;">Verify gateway dispatch, device ratchet
                                &amp; delivery latency</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="formAdminTestMessage">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1">Target Phone
                                Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 text-muted"><i
                                        class="fas fa-phone"></i></span>
                                <input type="text" id="testPhoneInput" name="phone"
                                    class="form-control bg-light border-0 text-xs font-monospace fw-bold"
                                    placeholder="e.g. 07901296858, +447901296858, or 918921569980" value="07901296858"
                                    required>
                            </div>
                            <span class="text-muted mt-1 d-block" style="font-size:11px;">Accepts UK local (07...),
                                international (+44...), or without country code.</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-xs fw-bold text-muted uppercase tracking-wider mb-1">Test Message
                                Content</label>
                            <textarea id="testMessageInput" name="message" rows="4"
                                class="form-control bg-light border-0 rounded-3 text-xs p-3 font-monospace" required
                                style="resize:none;">🔔 *PMCC-UK Admin Test Message* 🇬🇧

    Hello Admin,

    This is a live test notification confirming that the PMCC-UK WhatsApp Automation microservice is connected, online, and delivering messages properly.

    🌐 https://pmccuk.org</textarea>
                        </div>

                        <div id="testMessageAlert" class="alert d-none text-xs rounded-3 p-3 mb-0"></div>
                    </div>
                    <div class="modal-footer bg-light p-3 border-top d-flex justify-content-between">
                        <button type="button" class="btn btn-light btn-sm text-xs rounded-pill px-3"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btnSubmitAdminTest"
                            class="btn btn-success btn-sm rounded-pill text-xs fw-bold px-4 shadow-sm d-inline-flex align-items-center gap-1.5">
                            <i class="fas fa-paper-plane"></i>
                            <span>Dispatch Test Message</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ── 5. REVOKE & SESSION SECURITY MODAL ── -->
    <div class="modal fade" id="modalRevokeSession" tabindex="-1" aria-labelledby="modalRevokeSessionLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-danger-subtle border-bottom border-danger-subtle p-3.5">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="rounded-circle bg-danger text-white p-2"
                            style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h6 class="modal-title fw-black text-danger mb-0" id="modalRevokeSessionLabel">WhatsApp Session
                                Security &amp; Revocation</h6>
                            <span class="text-xxs text-muted">Manage linked device credentials and daemon lifecycle</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div
                        class="alert alert-warning border-0 bg-warning-subtle text-dark p-3 rounded-3 mb-3 text-xs d-flex align-items-start gap-2.5">
                        <i class="fas fa-exclamation-triangle text-warning fs-5 mt-0.5 flex-shrink-0"></i>
                        <div>
                            <strong>Security Notice:</strong> Revoking or resetting the session will unlink the
                            authenticated WhatsApp number. Automated ID card delivery, event passes, and 2-way bot replies
                            will pause until a new device is paired.
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <!-- Option 1: Standard Unlink -->
                        <div class="p-3 border rounded-3 bg-light hover-shadow transition">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark text-xs"><i class="fas fa-unlink text-danger me-1.5"></i>
                                    Standard Unlink (Graceful Logout)</span>
                                <span class="badge bg-secondary-subtle text-secondary text-xxs">Recommended</span>
                            </div>
                            <p class="text-muted text-xs mb-2.5">
                                Gracefully signs out of WhatsApp multi-device. Safely releases the connection while keeping
                                the daemon ready for a new scan.
                            </p>
                            <button type="button" id="btnExecuteLogout"
                                class="btn btn-outline-danger btn-sm rounded-pill text-xs fw-bold px-3 shadow-sm">
                                <i class="fas fa-sign-out-alt me-1"></i> Disconnect WhatsApp
                            </button>
                        </div>

                        <!-- Option 2: Force Purge & Hard Reset -->
                        <div
                            class="p-3 border border-danger-subtle rounded-3 bg-danger-subtle bg-opacity-10 hover-shadow transition">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-danger text-xs"><i
                                        class="fas fa-trash-alt text-danger me-1.5"></i> Force Purge Cache &amp; Regenerate
                                    QR</span>
                                <span class="badge bg-danger text-white text-xxs">Hard Reset</span>
                            </div>
                            <p class="text-muted text-xs mb-2.5">
                                Completely erases corrupted session credentials (<code
                                    class="text-danger">session_auth/</code>), drops active socket, and regenerates a
                                brand-new QR code instantly. Best if the connection is stuck or displaying encryption
                                errors.
                            </p>
                            <button type="button" id="btnExecuteForcePurge"
                                class="btn btn-danger btn-sm rounded-pill text-xs fw-bold px-3 shadow-sm">
                                <i class="fas fa-redo-alt me-1"></i> Force Purge &amp; Reset QR
                            </button>
                        </div>
                    </div>

                    <div id="revokeAlert" class="alert d-none text-xs rounded-3 p-3 mt-3 mb-0"></div>
                </div>
                <div class="modal-footer bg-light p-3 border-top">
                    <button type="button" class="btn btn-light btn-sm text-xs rounded-pill px-3"
                        data-bs-dismiss="modal">Close Window</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── JAVASCRIPT ENGINE ── -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
                ping: `Hello from Plymouth Malayalee Community (PMCC-UK)! 🌟\n\nThis is a test notification confirming that our WhatsApp automation service is active and operating correctly.\n\n🌐 https://pmccuk.org`,
                member: `Dear Member,\n\nCongratulations! Your PMCC-UK membership has been approved.\n\n🆔 Membership ID: PMCC-1052\n📅 Valid Until: 31 Dec 2027\n\nYour official digital ID Card is attached for entry and benefits.\n\nWarm regards,\nPMCC-UK Executive Committee`,
                ticket: `🎟️ PMCC-UK Event Ticket Confirmation\n\nDear Member,\nYour booking for 'Onam Celebration 2026' has been approved.\n\nRef: BOOK-1052\nTotal Attendees: 4 (2 Adults, 2 Kids)\n\nPlease present the attached PDF pass with QR code at the entrance desk.\n\nSee you there! 🎉`,
                otp: `PMCC-UK Verification Code: 582910\n\nUse this one-time code to complete your Event Booking verification.\n\nDo not share this code with anyone.\n(Valid for 10 minutes)`
            };

            presetChips.forEach(chip => {
                chip.addEventListener('click', function () {
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
                            qrImage.style.setProperty('display', 'block', 'important');
                            qrSpinner.style.setProperty('display', 'none', 'important');
                            if (laserLine) laserLine.style.display = 'block';

                            statusBadge.innerHTML = '<span class="badge bg-warning text-dark border-0 px-3 py-1.5 rounded-pill text-xs fw-bold d-inline-flex align-items-center gap-1.5"><span class="radar-dot amber"></span> Scan QR Code</span>';
                            statusText.textContent = 'Pairing Required';
                            subText.textContent = 'Multi-Device socket online. Scan the viewfinder QR code on this page.';
                            if (btnLogoutSession) btnLogoutSession.classList.add('d-none');
                        } else {
                            // Offline / Connecting
                            panelConnected.classList.add('d-none');
                            panelQr.classList.remove('d-none');
                            qrImage.style.setProperty('display', 'none', 'important');
                            qrSpinner.style.setProperty('display', 'flex', 'important');
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
            pollInterval = setInterval(function () {
                countdown--;
                if (timerVal) {
                    timerVal.textContent = countdown > 0 ? countdown : '15';
                }
                if (countdown <= 0) {
                    countdown = 15;
                    fetchQrAndStatus();
                }
            }, 1000);

            // Refresh Click
            document.getElementById('btnRefreshStatus')?.addEventListener('click', function () {
                countdown = 15;
                fetchQrAndStatus();
            });

            // ══════════════════════════════════════════════════════════
            //  LIVE WHATSAPP GATEWAY LOGS & BOT CONSOLE ENGINE
            // ══════════════════════════════════════════════════════════
            const terminalBody = document.getElementById('terminalBody');
            const logLinesContainer = document.getElementById('logLinesContainer');
            const logTotalBadge = document.getElementById('logTotalBadge');
            const logLiveBadge = document.getElementById('logLiveBadge');
            const logPollStatusText = document.getElementById('logPollStatusText');
            const chkAutoScroll = document.getElementById('chkAutoScroll');
            const chkAutoPoll = document.getElementById('chkAutoPoll');
            const logSearchInput = document.getElementById('logSearchInput');
            const btnRefreshLogs = document.getElementById('btnRefreshLogs');
            const btnClearLogs = document.getElementById('btnClearLogs');
            const btnDownloadLogs = document.getElementById('btnDownloadLogs');
            const iconRefreshLogs = document.getElementById('iconRefreshLogs');
            const logFilterButtons = document.querySelectorAll('.log-filter-btn');

            let allLogs = [];
            let currentFilter = 'all';
            let currentSearch = '';
            let lastLogId = 0;
            let isFetchingLogs = false;

            function getBadgeClass(type, level) {
                if (level === 'ERROR') return 'log-badge-error';
                if (level === 'WARNING') return 'log-badge-warn';
                switch (type) {
                    case 'inbound': return 'log-badge-inbound';
                    case 'outbound': return 'log-badge-outbound';
                    case 'bot_reply': return 'log-badge-outbound';
                    case 'qr': return 'log-badge-qr';
                    case 'auth': return 'log-badge-auth';
                    case 'system': return 'log-badge-system';
                    default: return 'log-badge-system';
                }
            }

            function formatTime(isoStr) {
                try {
                    const d = new Date(isoStr);
                    if (isNaN(d.getTime())) return isoStr;
                    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + '.' + String(d.getMilliseconds()).padStart(3, '0');
                } catch (e) {
                    return isoStr;
                }
            }

            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function renderLogs() {
                if (!logLinesContainer) return;

                let filtered = allLogs;

                // Apply type filter
                if (currentFilter !== 'all') {
                    if (currentFilter === 'bot') {
                        filtered = filtered.filter(l => l.type === 'inbound' || l.type === 'bot_reply');
                    } else if (currentFilter === 'outbound') {
                        filtered = filtered.filter(l => l.type === 'outbound' || l.type === 'bot_reply');
                    } else if (currentFilter === 'error') {
                        filtered = filtered.filter(l => l.level === 'ERROR' || l.level === 'WARNING' || l.type === 'error');
                    } else if (currentFilter === 'system') {
                        filtered = filtered.filter(l => l.type === 'system' || l.type === 'qr' || l.type === 'auth');
                    }
                }

                // Apply search filter
                if (currentSearch.trim()) {
                    const q = currentSearch.toLowerCase();
                    filtered = filtered.filter(l =>
                        (l.message && l.message.toLowerCase().includes(q)) ||
                        (l.type && l.type.toLowerCase().includes(q)) ||
                        (l.level && l.level.toLowerCase().includes(q)) ||
                        (l.details && JSON.stringify(l.details).toLowerCase().includes(q))
                    );
                }

                if (logTotalBadge) {
                    logTotalBadge.textContent = `${filtered.length} of ${allLogs.length} events`;
                }

                if (filtered.length === 0) {
                    logLinesContainer.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-terminal fs-4 mb-2 d-block opacity-50"></i>
                        <div class="text-xs">No console logs match the selected filter.</div>
                    </div>
                `;
                    return;
                }

                let html = '';
                for (const log of filtered) {
                    const badgeClass = getBadgeClass(log.type, log.level);
                    const timeStr = formatTime(log.timestamp);
                    const typeLabel = (log.type || 'SYSTEM').toUpperCase();
                    const hasDetails = log.details && Object.keys(log.details).length > 0;
                    const detailsId = `log-details-${log.id}`;

                    html += `
                    <div class="log-entry" id="log-row-${log.id}">
                        <span class="log-ts">[${timeStr}]</span>
                        <span class="log-badge-pill ${badgeClass}">${typeLabel}</span>
                        <div class="log-msg-text">
                            <span>${escapeHtml(log.message)}</span>
                            ${hasDetails ? `
                                <button class="btn btn-link p-0 text-secondary text-xxs ms-1.5 text-decoration-none" onclick="document.getElementById('${detailsId}').classList.toggle('d-none')">
                                    <i class="fas fa-code me-0.5"></i>payload
                                </button>
                                <pre id="${detailsId}" class="d-none mt-1 p-2 bg-black bg-opacity-50 rounded text-light text-xxs mb-0 font-monospace border border-dark">${escapeHtml(JSON.stringify(log.details, null, 2))}</pre>
                            ` : ''}
                        </div>
                    </div>
                `;
                }

                logLinesContainer.innerHTML = html;

                if (chkAutoScroll && chkAutoScroll.checked && terminalBody) {
                    terminalBody.scrollTop = terminalBody.scrollHeight;
                }
            }

            function fetchLogs(isManual = false) {
                if (isFetchingLogs) return;
                isFetchingLogs = true;

                if (isManual && iconRefreshLogs) {
                    iconRefreshLogs.classList.add('fa-spin');
                }

                const url = new URL('{{ route('admin.whatsapp.logs') }}', window.location.origin);
                url.searchParams.set('limit', 200);

                fetch(url.toString())
                    .then(res => res.json())
                    .then(data => {
                        if (isManual && iconRefreshLogs) {
                            iconRefreshLogs.classList.remove('fa-spin');
                        }
                        isFetchingLogs = false;

                        if (data && Array.isArray(data.logs)) {
                            allLogs = data.logs;
                            if (allLogs.length > 0) {
                                lastLogId = allLogs[allLogs.length - 1].id;
                            }
                            renderLogs();
                        }
                    })
                    .catch(err => {
                        if (isManual && iconRefreshLogs) {
                            iconRefreshLogs.classList.remove('fa-spin');
                        }
                        isFetchingLogs = false;
                        console.warn('[Log Fetch Error]', err);
                    });
            }

            // Filter Buttons
            logFilterButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    logFilterButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.getAttribute('data-filter');
                    renderLogs();
                });
            });

            // Search Input
            logSearchInput?.addEventListener('input', function () {
                currentSearch = this.value;
                renderLogs();
            });

            // Refresh Button
            btnRefreshLogs?.addEventListener('click', () => fetchLogs(true));

            // Clear Logs Button
            btnClearLogs?.addEventListener('click', function () {
                if (!confirm('Are you sure you want to clear the WhatsApp console log buffer?')) {
                    return;
                }

                fetch('{{ route('admin.whatsapp.logs.clear') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                    .then(res => res.json())
                    .then(data => {
                        allLogs = [];
                        lastLogId = 0;
                        renderLogs();
                    })
                    .catch(err => alert('Failed to clear logs: ' + err));
            });

            // Download Logs Button
            btnDownloadLogs?.addEventListener('click', function () {
                if (allLogs.length === 0) {
                    alert('No logs available to export.');
                    return;
                }

                const lines = allLogs.map(l => `[${l.timestamp}] [${(l.level || 'INFO').toUpperCase()}] [${(l.type || 'SYSTEM').toUpperCase()}] ${l.message} ${l.details ? JSON.stringify(l.details) : ''}`);
                const blob = new Blob([lines.join('\n')], { type: 'text/plain;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `pmcc-whatsapp-console-${new Date().toISOString().slice(0, 10)}.log`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });

            // Initial Log Fetch & Polling Interval (every 3 seconds)
            fetchLogs();
            setInterval(function () {
                if (chkAutoPoll && chkAutoPoll.checked) {
                    fetchLogs();
                }
            }, 3000);

            // ══════════════════════════════════════════════════════════
            //  REVOKE & SESSION SECURITY ENGINE
            // ══════════════════════════════════════════════════════════
            const modalRevokeEl = document.getElementById('modalRevokeSession');
            const revokeAlert = document.getElementById('revokeAlert');
            const btnExecuteLogout = document.getElementById('btnExecuteLogout');
            const btnExecuteForcePurge = document.getElementById('btnExecuteForcePurge');

            window.openRevokeModal = function (isForce = false) {
                if (modalRevokeEl) {
                    const bsModal = bootstrap.Modal.getOrCreateInstance(modalRevokeEl);
                    bsModal.show();
                }
            };

            function executeRevoke(force) {
                const targetBtn = force ? btnExecuteForcePurge : btnExecuteLogout;
                const originalHtml = targetBtn.innerHTML;

                targetBtn.disabled = true;
                targetBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5"></span> Revoking Session...';
                revokeAlert.className = 'alert d-none text-xs';

                fetch('{{ route('admin.whatsapp.revoke') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ force: force })
                })
                    .then(res => res.json())
                    .then(data => {
                        targetBtn.disabled = false;
                        targetBtn.innerHTML = originalHtml;

                        if (data.success) {
                            revokeAlert.className = 'alert alert-success text-xs border-0 bg-success-subtle text-success p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm';
                            revokeAlert.innerHTML = `<i class="fas fa-check-circle fs-5"></i> <div><strong>Session Revoked!</strong> ${data.message}</div>`;

                            // Instantly switch viewfinder to QR panel and show spinner
                            panelConnected.classList.add('d-none');
                            panelQr.classList.remove('d-none');
                            qrImage.style.display = 'none';
                            qrSpinner.style.display = 'flex';
                            if (laserLine) laserLine.style.display = 'none';

                            setTimeout(() => {
                                const bsModal = bootstrap.Modal.getInstance(modalRevokeEl);
                                if (bsModal) bsModal.hide();
                                revokeAlert.className = 'alert d-none';
                                countdown = 2;
                                fetchQrAndStatus();
                                fetchLogs(true);
                            }, 1400);
                        } else {
                            revokeAlert.className = 'alert alert-danger text-xs border-0 bg-danger-subtle text-danger p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm';
                            revokeAlert.innerHTML = `<i class="fas fa-exclamation-circle fs-5"></i> <div><strong>Failed:</strong> ${data.message || 'Could not revoke session.'}</div>`;
                        }
                    })
                    .catch(err => {
                        targetBtn.disabled = false;
                        targetBtn.innerHTML = originalHtml;
                        revokeAlert.className = 'alert alert-danger text-xs border-0 bg-danger-subtle text-danger p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm';
                        revokeAlert.innerHTML = `<i class="fas fa-wifi fs-5"></i> <div><strong>Network Error:</strong> ${err}</div>`;
                    });
            }

            btnExecuteLogout?.addEventListener('click', () => executeRevoke(false));
            btnExecuteForcePurge?.addEventListener('click', () => executeRevoke(true));

            // Test Message Dispatch
            formSendTest?.addEventListener('submit', function (e) {
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

            // ── Broadcast Studio Logic ──
            const formBroadcast = document.getElementById('formBroadcast');
            const broadcastAlert = document.getElementById('broadcastAlert');
            const btnSubmitBroadcast = document.getElementById('btnSubmitBroadcast');
            const broadcastMessage = document.getElementById('broadcastMessage');
            const customNumbersWrap = document.getElementById('customNumbersWrap');
            const audienceRadios = document.querySelectorAll('input[name="broadcast_audience"]');
            const bcastChips = document.querySelectorAll('.bcast-chip');

            // Toggle custom numbers textarea
            audienceRadios.forEach(radio => {
                radio.addEventListener('change', function () {
                    if (this.value === 'custom') {
                        customNumbersWrap.classList.remove('d-none');
                    } else {
                        customNumbersWrap.classList.add('d-none');
                    }
                });
            });

            // Broadcast Presets
            const bcastPresets = {
                onam: `🌸 Warmest Onam Greetings from PMCC-UK! May this festive season bring immense joy, health, and prosperity to you and your family. Join our grand celebrations: https://pmccuk.org/events`,
                xmas: `🎄 Merry Christmas & Happy New Year from the Plymouth Malayalee Community Club! Thank you for being a wonderful part of our community. Wishing you peace and happiness in the year ahead.`,
                weather: `⚠️ PMCC-UK Urgent Announcement: Due to adverse weather warnings, please note that today's event venue has been updated to Plymouth Guildhall. For assistance, visit https://pmccuk.org`,
                student: `🎓 Welcome to Plymouth, dear Student! PMCC-UK is delighted to welcome you to the UK. Need help with NHS registration, accommodation, or community support? Visit our Student Corner: https://pmccuk.org/student-corner or text STUDENT to this WhatsApp number anytime!`
            };

            bcastChips.forEach(chip => {
                chip.addEventListener('click', function () {
                    bcastChips.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    const bType = this.getAttribute('data-bcast');
                    if (bcastPresets[bType] && broadcastMessage) {
                        broadcastMessage.value = bcastPresets[bType];
                    }
                });
            });

            // Form submission
            formBroadcast?.addEventListener('submit', function (e) {
                e.preventDefault();
                const audience = document.querySelector('input[name="broadcast_audience"]:checked')?.value || 'members';
                const msg = broadcastMessage?.value || '';
                const customNums = document.getElementById('broadcastCustomNumbers')?.value || '';

                if (!confirm('Are you sure you want to launch this broadcast to the selected audience segment?')) {
                    return;
                }

                btnSubmitBroadcast.disabled = true;
                btnSubmitBroadcast.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Dispatching Community Broadcast...';
                broadcastAlert.className = 'alert d-none text-xs';

                fetch('{{ route('admin.whatsapp.broadcast') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        audience: audience,
                        message: msg,
                        custom_numbers: customNums
                    })
                })
                    .then(res => res.json())
                    .then(data => {
                        btnSubmitBroadcast.disabled = false;
                        btnSubmitBroadcast.innerHTML = '<i class="fas fa-paper-plane text-success"></i> <span>Launch Safe Community Broadcast</span>';

                        if (data.success) {
                            broadcastAlert.className = 'alert alert-success text-xs border-0 bg-success-subtle text-success p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm';
                            broadcastAlert.innerHTML = `<i class="fas fa-check-circle fs-5"></i> <div><strong>Broadcast Completed!</strong> ${data.message}</div>`;
                        } else {
                            broadcastAlert.className = 'alert alert-danger text-xs border-0 bg-danger-subtle text-danger p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm';
                            broadcastAlert.innerHTML = `<i class="fas fa-exclamation-circle fs-5"></i> <div><strong>Failed:</strong> ${data.message || 'Error occurred.'}</div>`;
                        }
                    })
                    .catch(err => {
                        btnSubmitBroadcast.disabled = false;
                        btnSubmitBroadcast.innerHTML = '<i class="fas fa-paper-plane text-success"></i> <span>Launch Safe Community Broadcast</span>';
                        broadcastAlert.className = 'alert alert-danger text-xs border-0 bg-danger-subtle text-danger p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm';
                        broadcastAlert.innerHTML = `<i class="fas fa-wifi fs-5"></i> <div><strong>Network Error:</strong> ${err}</div>`;
                    });
            });

            // ── Admin Test Message Dispatch Logic ──
            const formAdminTest = document.getElementById('formAdminTestMessage');
            const adminTestAlert = document.getElementById('testMessageAlert');
            const btnSubmitAdminTestModal = document.getElementById('btnSubmitAdminTest');

            formAdminTest?.addEventListener('submit', function (e) {
                e.preventDefault();
                const phone = document.getElementById('testPhoneInput')?.value.trim();
                const message = document.getElementById('testMessageInput')?.value.trim();

                if (!phone || !message) return;

                btnSubmitAdminTestModal.disabled = true;
                btnSubmitAdminTestModal.innerHTML = '<span class="spinner-border spinner-border-sm me-1.5"></span> Dispatching...';
                adminTestAlert.className = 'alert d-none text-xs';

                fetch('{{ route('admin.whatsapp.send-test') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ phone, message })
                })
                    .then(res => res.json())
                    .then(data => {
                        btnSubmitAdminTestModal.disabled = false;
                        btnSubmitAdminTestModal.innerHTML = '<i class="fas fa-paper-plane"></i> <span>Dispatch Test Message</span>';

                        if (data.success) {
                            adminTestAlert.className = 'alert alert-success text-xs border-0 bg-success-subtle text-success p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm mt-3';
                            adminTestAlert.innerHTML = `<i class="fas fa-check-circle fs-5"></i> <div><strong>Delivered!</strong> ${data.message}</div>`;
                            if (typeof fetchLogs === 'function') fetchLogs();
                        } else {
                            adminTestAlert.className = 'alert alert-danger text-xs border-0 bg-danger-subtle text-danger p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm mt-3';
                            adminTestAlert.innerHTML = `<i class="fas fa-exclamation-circle fs-5"></i> <div><strong>Failed:</strong> ${data.message || 'Could not send test message.'}</div>`;
                        }
                    })
                    .catch(err => {
                        btnSubmitAdminTestModal.disabled = false;
                        btnSubmitAdminTestModal.innerHTML = '<i class="fas fa-paper-plane"></i> <span>Dispatch Test Message</span>';
                        adminTestAlert.className = 'alert alert-danger text-xs border-0 bg-danger-subtle text-danger p-3 rounded-3 d-flex align-items-center gap-2 shadow-sm mt-3';
                        adminTestAlert.innerHTML = `<i class="fas fa-wifi fs-5"></i> <div><strong>Network Error:</strong> ${err}</div>`;
                    });
            });

            // Quick Test Admin Alert Trigger from Telemetry Card
            const btnTestAdminAlertHub = document.getElementById('btnTestAdminAlertFromWaHub');
            btnTestAdminAlertHub?.addEventListener('click', function () {
                if (!confirm('Dispatch a live synchronized Test Admin Security Alert to WhatsApp & Telegram right now?')) {
                    return;
                }

                btnTestAdminAlertHub.disabled = true;
                btnTestAdminAlertHub.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Dispatching...';

                fetch('{{ route('admin.whatsapp.test-admin-alert') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                    .then(res => res.json())
                    .then(data => {
                        btnTestAdminAlertHub.disabled = false;
                        btnTestAdminAlertHub.innerHTML = '<i class="fas fa-bell text-warning"></i> <span>Test Admin Alert</span>';
                        alert(data.message || (data.success ? 'Delivered!' : 'Failed.'));
                        if (typeof fetchLogs === 'function') fetchLogs();
                    })
                    .catch(err => {
                        btnTestAdminAlertHub.disabled = false;
                        btnTestAdminAlertHub.innerHTML = '<i class="fas fa-bell text-warning"></i> <span>Test Admin Alert</span>';
                        alert('Network error: ' + err);
                    });
            });
        });
    </script>
@endsection