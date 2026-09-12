@extends('layouts.admin')

@section('page_title', 'Master System Configuration')

@section('content')
<div class="row">
    <div class="col-md-12">
        <form action="{{ route('admin.config.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-5">
                <div class="card-header bg-white p-0 border-0">
                    <ul class="nav nav-pills nav-fill bg-light p-2" id="settingsTabs" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active fw-bold py-3" data-bs-toggle="pill" data-bs-target="#brandingTab">
                                <i class="fas fa-globe me-2"></i> BRANDING & CONTACT
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link fw-bold py-3" data-bs-toggle="pill" data-bs-target="#homeTab">
                                <i class="fas fa-home me-2"></i> HOME PAGE
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link fw-bold py-3" data-bs-toggle="pill" data-bs-target="#aboutTab">
                                <i class="fas fa-info-circle me-2"></i> ABOUT US
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link fw-bold py-3" data-bs-toggle="pill" data-bs-target="#seoTab">
                                <i class="fas fa-search me-2"></i> SEO & SOCIAL
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link fw-bold py-3" data-bs-toggle="pill" data-bs-target="#notificationsTab">
                                <i class="fab fa-telegram-plane me-2"></i> TELEGRAM ALERTS
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link fw-bold py-3" data-bs-toggle="pill" data-bs-target="#whatsappTab">
                                <i class="fab fa-whatsapp me-2 text-success"></i> WHATSAPP
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-5">
                    <div class="tab-content">
                        <!-- BRANDING & CONTACT -->
                        <div class="tab-pane fade show active" id="brandingTab">
                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label fw-black small text-uppercase tracking-wider">Site Name</label>
                                    <input type="text" name="site_name" class="form-control form-control-lg bg-light border-0" value="{{ $settings['site_name'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-black small text-uppercase tracking-wider">Tagline</label>
                                    <input type="text" name="tagline" class="form-control form-control-lg bg-light border-0" value="{{ $settings['tagline'] ?? '' }}">
                                </div>
                            </div>

                            <h6 class="fw-bold mb-4 border-bottom pb-2 text-primary uppercase small italic">Global Contact Details</h6>
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Primary Email</label>
                                    <input type="email" name="contact_email" class="form-control" value="{{ $settings['contact_email'] ?? '' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Contact Phone</label>
                                    <input type="text" name="contact_phone" class="form-control" value="{{ $settings['contact_phone'] ?? '' }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Office Address</label>
                                    <input type="text" name="contact_address" class="form-control" value="{{ $settings['contact_address'] ?? '' }}">
                                </div>
                            </div>
                        </div>

                        <!-- HOME PAGE CONTENT -->
                        <div class="tab-pane fade" id="homeTab">
                            <div class="row g-4 mb-5">
                                <div class="col-md-12">
                                    <h6 class="fw-bold mb-3 text-secondary">Hero Banner Configuration</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Hero Title (HTML Allowed)</label>
                                    <textarea name="hero_title" class="form-control" rows="2">{{ $settings['hero_title'] ?? '' }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Hero Description</label>
                                    <textarea name="hero_desc" class="form-control" rows="2">{{ $settings['hero_desc'] ?? '' }}</textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Hero Banner Image</label>
                                    @if(!empty($settings['hero_image']))
                                        <div class="mb-3">
                                            <img src="{{ asset('storage/' . $settings['hero_image']) }}" class="rounded shadow-sm" style="max-height: 100px;">
                                        </div>
                                    @endif
                                    <input type="file" name="hero_image_file" class="form-control mb-2">
                                    <label class="form-label small text-muted">Or keep current path:</label>
                                    <input type="text" name="hero_image" class="form-control form-control-sm text-muted" value="{{ $settings['hero_image'] ?? '' }}">
                                </div>
                            </div>

                            <div class="row g-4 border-top pt-5">
                                <div class="col-md-12">
                                    <h6 class="fw-bold mb-3 text-secondary">President's Message</h6>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">President Name</label>
                                    <input type="text" name="pres_name" class="form-control mb-4" value="{{ $settings['pres_name'] ?? '' }}">
                                    
                                    <label class="form-label fw-bold">President Photo</label>
                                    @if(!empty($settings['pres_image']))
                                        <div class="mb-3">
                                            <img src="{{ asset('storage/' . $settings['pres_image']) }}" class="rounded shadow-sm" style="max-height: 100px;">
                                        </div>
                                    @endif
                                    <input type="file" name="pres_image_file" class="form-control mb-2">
                                    <label class="form-label small text-muted">Current path:</label>
                                    <input type="text" name="pres_image" class="form-control form-control-sm text-muted" value="{{ $settings['pres_image'] ?? '' }}">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Message Content</label>
                                    <textarea name="pres_msg" class="form-control" rows="6">{{ $settings['pres_msg'] ?? '' }}</textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">News Ticker Text</label>
                                    <input type="text" name="ticker_text" class="form-control" value="{{ $settings['ticker_text'] ?? '' }}">
                                </div>
                            </div>
                        </div>

                        <!-- ABOUT US CONTENT -->
                        <div class="tab-pane fade" id="aboutTab">
                            <div class="mb-5">
                                <label class="form-label fw-bold h5">Main Story Title</label>
                                <input type="text" name="about_title" class="form-control form-control-lg" value="{{ $settings['about_title'] ?? '' }}">
                            </div>
                            <div class="mb-5">
                                <label class="form-label fw-bold h5">Our Detailed Story</label>
                                <textarea name="about_content" class="form-control" rows="10">{{ $settings['about_content'] ?? '' }}</textarea>
                            </div>
                            <div class="mb-5">
                                <label class="form-label fw-bold h5">About Page Main Image</label>
                                @if(!empty($settings['about_image']))
                                    <div class="mb-3">
                                        <img src="{{ asset('storage/' . $settings['about_image']) }}" class="rounded shadow-sm" style="max-height: 150px;">
                                    </div>
                                @endif
                                <input type="file" name="about_image_file" class="form-control mb-2">
                                <label class="form-label small text-muted">Or keep current path:</label>
                                <input type="text" name="about_image" class="form-control form-control-sm text-muted" value="{{ $settings['about_image'] ?? '' }}">
                            </div>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="p-4 bg-primary-subtle rounded-3">
                                        <label class="form-label fw-bold text-primary">Vision Title</label>
                                        <input type="text" name="about_vision_title" class="form-control mb-3" value="{{ $settings['about_vision_title'] ?? 'Our Vision' }}">
                                        <label class="form-label fw-bold text-primary">Vision Content</label>
                                        <textarea name="about_vision_content" class="form-control" rows="4">{{ $settings['about_vision_content'] ?? '' }}</textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-4 bg-secondary-subtle rounded-3">
                                        <label class="form-label fw-bold text-secondary">Mission Title</label>
                                        <input type="text" name="about_mission_title" class="form-control mb-3" value="{{ $settings['about_mission_title'] ?? 'Our Mission' }}">
                                        <label class="form-label fw-bold text-secondary">Mission Content</label>
                                        <textarea name="about_mission_content" class="form-control" rows="4">{{ $settings['about_mission_content'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEO & SOCIAL -->
                        <div class="tab-pane fade" id="seoTab">
                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">SEO Site Title</label>
                                    <input type="text" name="seo_title" class="form-control" value="{{ $settings['seo_title'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">SEO Keywords</label>
                                    <input type="text" name="seo_keywords" class="form-control" value="{{ $settings['seo_keywords'] ?? '' }}">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">SEO Description</label>
                                    <textarea name="seo_description" class="form-control" rows="3">{{ $settings['seo_description'] ?? '' }}</textarea>
                                </div>
                            </div>

                            <div class="row g-4 pt-5 border-top">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold"><i class="fab fa-facebook me-1"></i> Facebook</label>
                                    <input type="url" name="social_facebook" class="form-control" value="{{ $settings['social_facebook'] ?? '' }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold"><i class="fab fa-instagram me-1"></i> Instagram</label>
                                    <input type="url" name="social_instagram" class="form-control" value="{{ $settings['social_instagram'] ?? '' }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold"><i class="fab fa-youtube me-1"></i> YouTube</label>
                                    <input type="url" name="social_youtube" class="form-control" value="{{ $settings['social_youtube'] ?? '' }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold"><i class="fab fa-twitter me-1"></i> Twitter</label>
                                    <input type="url" name="social_twitter" class="form-control" value="{{ $settings['social_twitter'] ?? '' }}">
                                </div>
                            </div>
                        </div>

                        <!-- TELEGRAM NOTIFICATIONS -->
                        <div class="tab-pane fade" id="notificationsTab">
                            <h6 class="fw-bold mb-4 border-bottom pb-2 text-primary uppercase small italic">Telegram Bot Alerts Configuration</h6>
                            <p class="text-muted mb-4">Provide your Telegram Bot credentials to receive instant real-time alerts in your administrative group when members submit registration requests or event bookings.</p>
                            
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Telegram Bot Token</label>
                                    <input type="text" name="telegram_bot_token" class="form-control form-control-lg bg-light border-0" value="{{ $settings['telegram_bot_token'] ?? '' }}" placeholder="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ">
                                    <div class="form-text mt-1 text-muted">The API token obtained from Telegram's @BotFather.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Telegram Chat ID(s)</label>
                                    <input type="text" name="telegram_chat_id" class="form-control form-control-lg bg-light border-0" value="{{ $settings['telegram_chat_id'] ?? '' }}" placeholder="-1001234567890, -1009876543210">
                                    <div class="form-text mt-1 text-muted">The unique ID of the Telegram group/chat. Enter multiple IDs separated by commas or spaces to notify multiple chats.</div>
                                </div>
                            </div>
                        </div>

                        <!-- WHATSAPP AUTOMATION -->
                        <div class="tab-pane fade" id="whatsappTab">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                                <div>
                                    <h6 class="fw-bold mb-1 text-success text-uppercase small italic">
                                        <i class="fab fa-whatsapp me-1"></i> Open-WA / Baileys Service Configuration
                                    </h6>
                                    <p class="text-muted small mb-0">Configure the local Node.js WhatsApp daemon connection and toggle automated notification events.</p>
                                </div>
                                <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                    <i class="fas fa-qrcode me-1"></i> Open WhatsApp Hub & QR Scan
                                </a>
                            </div>

                            <!-- Master Enable Switch -->
                            <div class="card bg-light border-0 rounded-3 p-4 mb-4">
                                <div class="form-check form-switch d-flex align-items-center justify-content-between ps-0">
                                    <div>
                                        <label class="form-check-label fw-bold h6 mb-1 d-block" for="whatsapp_enabled">Enable WhatsApp Automation</label>
                                        <span class="text-muted small">When turned on, the system will automatically send member cards, tickets, and OTPs via WhatsApp.</span>
                                    </div>
                                    <input type="hidden" name="whatsapp_enabled" value="0">
                                    <input class="form-check-input ms-3" type="checkbox" role="switch" name="whatsapp_enabled" id="whatsapp_enabled" value="1" style="width:3.2em; height:1.7em;" {{ ($settings['whatsapp_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                                </div>
                            </div>

                            <!-- Gateway Settings -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Daemon Microservice URL</label>
                                    <input type="text" name="whatsapp_server_url" class="form-control form-control-lg bg-light border-0" value="{{ $settings['whatsapp_server_url'] ?? 'http://127.0.0.1:8085' }}" placeholder="http://127.0.0.1:8085">
                                    <div class="form-text mt-1 text-muted">The internal address where the Node.js WhatsApp daemon process is running.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">API Secret Key (Bearer Token)</label>
                                    <input type="text" name="whatsapp_api_key" class="form-control form-control-lg bg-light border-0" value="{{ $settings['whatsapp_api_key'] ?? 'pmcc_wa_sec_key_2026_x9' }}" placeholder="Enter secret API token">
                                    <div class="form-text mt-1 text-muted">Must match the API_KEY defined in whatsapp-server/.env.</div>
                                </div>
                            </div>

                            <!-- Feature Toggles -->
                            <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary uppercase small italic">Notification Dispatch Rules</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 bg-white h-100 d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-bold text-sm mb-0"><i class="fas fa-id-card text-primary me-2"></i> Send Member ID Cards</div>
                                            <small class="text-muted text-xs">Deliver Member ID Card PDF automatically upon application approval.</small>
                                        </div>
                                        <input type="hidden" name="whatsapp_notify_id_card" value="0">
                                        <input class="form-check-input" type="checkbox" name="whatsapp_notify_id_card" value="1" {{ ($settings['whatsapp_notify_id_card'] ?? '1') === '1' ? 'checked' : '' }}>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 bg-white h-100 d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-bold text-sm mb-0"><i class="fas fa-ticket-alt text-warning me-2"></i> Send Event Ticket PDFs</div>
                                            <small class="text-muted text-xs">Deliver event admission pass and QR code upon booking confirmation.</small>
                                        </div>
                                        <input type="hidden" name="whatsapp_notify_event_ticket" value="0">
                                        <input class="form-check-input" type="checkbox" name="whatsapp_notify_event_ticket" value="1" {{ ($settings['whatsapp_notify_event_ticket'] ?? '1') === '1' ? 'checked' : '' }}>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 bg-white h-100 d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-bold text-sm mb-0"><i class="fas fa-key text-info me-2"></i> Send Verification OTPs</div>
                                            <small class="text-muted text-xs">Deliver 6-digit registration / booking OTP to mobile via WhatsApp.</small>
                                        </div>
                                        <input type="hidden" name="whatsapp_notify_otp" value="0">
                                        <input class="form-check-input" type="checkbox" name="whatsapp_notify_otp" value="1" {{ ($settings['whatsapp_notify_otp'] ?? '1') === '1' ? 'checked' : '' }}>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 bg-white h-100 d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="fw-bold text-sm mb-0"><i class="fas fa-shield-alt text-danger me-2"></i> Admin Security Alerts</div>
                                            <small class="text-muted text-xs">Dispatch urgent intrusion / failed login alerts to admin numbers.</small>
                                        </div>
                                        <input type="hidden" name="whatsapp_notify_admin_security" value="0">
                                        <input class="form-check-input" type="checkbox" name="whatsapp_notify_admin_security" value="1" {{ ($settings['whatsapp_notify_admin_security'] ?? '0') === '1' ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>

                            <!-- Admin Alert Recipient Numbers -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Admin Mobile Number(s) for Security Alerts</label>
                                <input type="text" name="whatsapp_admin_numbers" class="form-control bg-light border-0" value="{{ $settings['whatsapp_admin_numbers'] ?? '' }}" placeholder="e.g. 07123456789, +447987654321">
                                <div class="form-text text-muted">Comma-separated mobile numbers that should receive admin security alerts.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white border-0 p-4 sticky-bottom shadow-lg text-center">
                    <button type="submit" class="btn btn-primary px-5 py-3 fw-black rounded-pill shadow-lg hover-up">
                        <i class="fas fa-check-circle me-2 scale-150"></i> APPLY SYSTEM CONFIGURATION
                    </button>
                    <p class="text-xs text-muted mt-3 mb-0 uppercase tracking-widest font-black">Warning: Changes are applied globally to the public website immediately.</p>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    #settingsTabs .nav-link { 
        color: #64748b; 
        border-radius: 0.75rem;
        transition: all 0.3s ease;
    }
    #settingsTabs .nav-link.active { 
        background: white !important; 
        color: var(--bs-primary) !important; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .form-control:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        
        if (tab === 'home') {
            const el = document.querySelector('[data-bs-target="#homeTab"]');
            if (el) bootstrap.Tab.getOrCreateInstance(el).show();
        } else if (tab === 'about') {
            const el = document.querySelector('[data-bs-target="#aboutTab"]');
            if (el) bootstrap.Tab.getOrCreateInstance(el).show();
        } else if (tab === 'seo') {
            const el = document.querySelector('[data-bs-target="#seoTab"]');
            if (el) bootstrap.Tab.getOrCreateInstance(el).show();
        } else if (tab === 'notifications') {
            const el = document.querySelector('[data-bs-target="#notificationsTab"]');
            if (el) bootstrap.Tab.getOrCreateInstance(el).show();
        } else if (tab === 'whatsapp' || window.location.hash === '#tab-whatsapp') {
            const el = document.querySelector('[data-bs-target="#whatsappTab"]');
            if (el) bootstrap.Tab.getOrCreateInstance(el).show();
        }
    });
</script>
@endsection
