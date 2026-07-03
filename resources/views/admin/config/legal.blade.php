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
                    <ul class="nav nav-tabs nav-fill mb-4" id="legalTabs" role="tablist">
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
                                <i class="fas fa-cookie-bite me-2"></i> Cookie & Disclosure Policy
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
    });
</script>
@endsection
