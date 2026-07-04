@extends('layouts.admin')

@section('page_title', 'Legal Policies Management')

@section('content')
<div class="row">
    <div class="col-md-12">
        <form action="{{ route('admin.config.settings.update') }}" method="POST">
            @csrf
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-5">
                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title fw-bold mb-0 text-primary"><i class="fas fa-gavel me-2"></i> Legal Policies</h5>
                        <p class="text-muted small mb-0 mt-1">Configure Terms & Conditions, Privacy Policy and other agreements shown on the site.</p>
                    </div>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold">
                        <i class="fas fa-save me-1"></i> Save Policies
                    </button>
                </div>
                <div class="card-body p-4">
                    <ul class="nav nav-tabs mb-4" id="legalTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms" type="button" role="tab">
                                <i class="fas fa-file-contract me-2"></i> Terms & Conditions
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab">
                                <i class="fas fa-user-shield me-2"></i> Privacy Policy
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" id="cookies-tab" data-bs-toggle="tab" data-bs-target="#cookies" type="button" role="tab">
                                <i class="fas fa-cookie-bite me-2"></i> Cookie Policy
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" id="refund-tab" data-bs-toggle="tab" data-bs-target="#refund" type="button" role="tab">
                                <i class="fas fa-undo-alt me-2"></i> Refund & Cancellation
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" id="safeguarding-tab" data-bs-toggle="tab" data-bs-target="#safeguarding" type="button" role="tab">
                                <i class="fas fa-child me-2"></i> Safeguarding
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" id="conduct-tab" data-bs-toggle="tab" data-bs-target="#conduct" type="button" role="tab">
                                <i class="fas fa-handshake me-2"></i> Code of Conduct
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" id="accessibility-tab" data-bs-toggle="tab" data-bs-target="#accessibility" type="button" role="tab">
                                <i class="fas fa-universal-access me-2"></i> Accessibility
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="legalTabsContent">
                        <!-- TERMS AND CONDITIONS -->
                        <div class="tab-pane fade show active" id="terms" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Terms & Conditions Agreement</label>
                                <textarea name="legal_terms_conditions" id="terms_editor" class="form-control" rows="12">{{ $settings['legal_terms_conditions'] ?? 'The membership fee is £5 per annum for both families and individuals. The year runs from January to December. Your data is protected under PMCC\'s privacy policy and used solely for community communication.' }}</textarea>
                                <div class="form-text mt-1 text-muted">This agreement is shown to members during the registration and renewal processes.</div>
                            </div>
                        </div>

                        <!-- PRIVACY POLICY -->
                        <div class="tab-pane fade" id="privacy" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Privacy Policy Details</label>
                                <textarea name="legal_privacy_policy" id="privacy_editor" class="form-control" rows="12">{{ $settings['legal_privacy_policy'] ?? 'We value your privacy. Your community communication details and PII are stored securely and encrypted in our database.' }}</textarea>
                                <div class="form-text mt-1 text-muted">Details how the community collects, processes, and protects member data under GDPR.</div>
                            </div>
                        </div>

                        <!-- COOKIE POLICY -->
                        <div class="tab-pane fade" id="cookies" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Cookie & System Disclosure</label>
                                <textarea name="legal_cookie_policy" id="cookies_editor" class="form-control" rows="12">{{ $settings['legal_cookie_policy'] ?? 'Our system uses cookies solely for authentication and session management to ensure a smooth administrative experience.' }}</textarea>
                                <div class="form-text mt-1 text-muted">Describes cookies and background activities to users.</div>
                            </div>
                        </div>

                        <!-- REFUND & CANCELLATION -->
                        <div class="tab-pane fade" id="refund" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Refund & Cancellation Policy (Events)</label>
                                <textarea name="legal_refund_cancellation" id="refund_editor" class="form-control" rows="12">{{ $settings['legal_refund_cancellation'] ?? 'Tickets purchased for PMCC events are non-refundable except in cases where the event is cancelled or rescheduled. Under exceptional circumstances, refund requests submitted 7 days prior to the event may be considered by the committee.' }}</textarea>
                                <div class="form-text mt-1 text-muted">Details regarding ticket bookings refund policies.</div>
                            </div>
                        </div>

                        <!-- SAFEGUARDING POLICY -->
                        <div class="tab-pane fade" id="safeguarding" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Safeguarding Policy</label>
                                <textarea name="legal_safeguarding" id="safeguarding_editor" class="form-control" rows="12">{{ $settings['legal_safeguarding'] ?? 'PMCC is committed to safeguarding children, young people, and vulnerable adults. We ensure all activities involving children are conducted with proper supervision, DBS-checked volunteers where necessary, and compliance with local safeguarding guidelines.' }}</textarea>
                                <div class="form-text mt-1 text-muted">Safeguarding policy for children and vulnerable groups during community events.</div>
                            </div>
                        </div>

                        <!-- CODE OF CONDUCT -->
                        <div class="tab-pane fade" id="conduct" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Member Code of Conduct</label>
                                <textarea name="legal_code_of_conduct" id="conduct_editor" class="form-control" rows="12">{{ $settings['legal_code_of_conduct'] ?? 'Members are expected to treat all other community members, volunteers, and guests with respect, dignity, and inclusivity. Harassment, discrimination, or abusive behaviour during events or on community platforms will result in termination of membership.' }}</textarea>
                                <div class="form-text mt-1 text-muted">Sets behavioral standards and rules for community members.</div>
                            </div>
                        </div>

                        <!-- ACCESSIBILITY STATEMENT -->
                        <div class="tab-pane fade" id="accessibility" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Accessibility Statement</label>
                                <textarea name="legal_accessibility" id="accessibility_editor" class="form-control" rows="12">{{ $settings['legal_accessibility'] ?? 'PMCC is committed to making its website and cultural events accessible to everyone, including individuals with disabilities. We strive to improve web accessibility in accordance with WCAG 2.1 guidelines.' }}</textarea>
                                <div class="form-text mt-1 text-muted">Accessibility policy details and accommodations.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light py-3 text-end">
                    <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<style>
    .note-editor {
        border-radius: 8px !important;
        overflow: hidden;
        border: 1px solid #dee2e6 !important;
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    $(document).ready(function() {
        const summernoteConfig = {
            height: 350,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview', 'help']],
            ]
        };
        $('#terms_editor').summernote(summernoteConfig);
        $('#privacy_editor').summernote(summernoteConfig);
        $('#cookies_editor').summernote(summernoteConfig);
        $('#refund_editor').summernote(summernoteConfig);
        $('#safeguarding_editor').summernote(summernoteConfig);
        $('#conduct_editor').summernote(summernoteConfig);
        $('#accessibility_editor').summernote(summernoteConfig);
    });
</script>
@endsection
