@extends('layouts.admin')

@section('page_title', 'Email (SMTP) Settings')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-envelope-config mr-2"></i> Mail Server Configuration</h3>
            </div>
            <form action="{{ route('admin.email-settings.update') }}" method="POST">
                @csrf
                <div class="card-body">
                    <p class="text-muted mb-4 small">Configure your outgoing mail server (SMTP). These settings will override your .env file values.</p>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group mb-4">
                                <label>SMTP Host</label>
                                <input type="text" name="mail_host" class="form-control" value="{{ $email_settings->get('mail_host') }}" placeholder="e.g. smtp.gmail.com">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-4">
                                <label>Port</label>
                                <input type="text" name="mail_port" class="form-control" value="{{ $email_settings->get('mail_port') }}" placeholder="587">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label>Encryption</label>
                        <select name="mail_encryption" class="form-control">
                            <option value="tls" {{ $email_settings->get('mail_encryption') == 'tls' ? 'selected' : '' }}>TLS (Recommended)</option>
                            <option value="ssl" {{ $email_settings->get('mail_encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="" {{ $email_settings->get('mail_encryption') == '' ? 'selected' : '' }}>None</option>
                        </select>
                    </div>

                    <div class="form-group mb-4">
                        <label>SMTP Username</label>
                        <input type="text" name="mail_username" class="form-control" value="{{ $email_settings->get('mail_username') }}">
                    </div>

                    <div class="form-group mb-4">
                        <label>SMTP Password</label>
                        <div class="input-group">
                            <input type="password" name="mail_password" id="mail_password" class="form-control" value="{{ $email_settings->get('mail_password') }}">
                            <div class="input-group-append">
                                <span class="input-group-text" onclick="togglePass()" style="cursor: pointer;">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group mb-4 mt-4">
                        <label>Send Emails From (Address)</label>
                        <input type="email" name="mail_from_address" class="form-control" value="{{ $email_settings->get('mail_from_address') }}" placeholder="noreply@pmcc.uk">
                    </div>

                    <div class="form-group mb-0">
                        <label>Sender Name</label>
                        <input type="text" name="mail_from_name" class="form-control" value="{{ $email_settings->get('mail_from_name') }}" placeholder="PMCC UK">
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary px-5">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-info">
            <div class="card-body">
                <h5 class="font-weight-bold"><i class="fas fa-info-circle mr-2"></i> How to set up?</h5>
                <hr class="border-white opacity-25">
                <p class="small">If you are using <b>Gmail</b>:</p>
                <ul class="small">
                    <li>Host: <code>smtp.gmail.com</code></li>
                    <li>Port: <code>587</code></li>
                    <li>Encryption: <code>tls</code></li>
                    <li>Password: Use an <b>App Password</b> from your Google Account settings.</li>
                </ul>
                <p class="small"><b>Tip:</b> Changes take effect immediately without needing to restart the server.</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title text-warning"><i class="fas fa-bug mr-2"></i> SMTP Debugger (Test Sending)</h3>
            </div>
            <form action="{{ route('admin.email-settings.test') }}" method="POST">
                @csrf
                <!-- Pass current field values hidden so we can test WITHOUT saving first -->
                <input type="hidden" name="mail_host" id="test_mail_host">
                <input type="hidden" name="mail_port" id="test_mail_port">
                <input type="hidden" name="mail_encryption" id="test_mail_encryption">
                <input type="hidden" name="mail_username" id="test_mail_username">
                <input type="hidden" name="mail_password" id="test_mail_password">
                <input type="hidden" name="mail_from_address" id="test_mail_from_address">
                <input type="hidden" name="mail_from_name" id="test_mail_from_name">

                <div class="card-body">
                    <p class="small text-muted">Use this tool to verify your settings. It will try to send a real email using the details currently typed in the form above.</p>
                    <div class="input-group">
                        <input type="email" name="test_email" class="form-control" placeholder="Enter recipient email (e.g. your personal email)" required>
                        <div class="input-group-append">
                            <button type="submit" onclick="syncFields()" class="btn btn-warning font-weight-bold">
                                <i class="fas fa-paper-plane mr-1"></i> Send Test & Debug
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function syncFields() {
        // Sync the main form values to the hidden test fields
        document.getElementById('test_mail_host').value = document.getElementsByName('mail_host')[0].value;
        document.getElementById('test_mail_port').value = document.getElementsByName('mail_port')[0].value;
        document.getElementById('test_mail_encryption').value = document.getElementsByName('mail_encryption')[0].value;
        document.getElementById('test_mail_username').value = document.getElementsByName('mail_username')[0].value;
        document.getElementById('test_mail_password').value = document.getElementById('mail_password').value;
        document.getElementById('test_mail_from_address').value = document.getElementsByName('mail_from_address')[0].value;
        document.getElementById('test_mail_from_name').value = document.getElementsByName('mail_from_name')[0].value;
    }

    function togglePass() {
        const p = document.getElementById('mail_password');
        const e = document.getElementById('eyeIcon');
        if (p.type === "password") {
            p.type = "text";
            e.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            p.type = "password";
            e.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>
@endsection
