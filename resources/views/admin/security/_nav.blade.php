<div class="card shadow-sm border-0 mb-4" style="border-radius:12px;background:#ffffff;">
    <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <ul class="nav nav-pills flex-wrap gap-1">
            <li class="nav-item">
                <a class="nav-link text-xs font-weight-bold {{ request()->routeIs('admin.security.overview') ? 'active bg-primary' : 'text-dark' }}" 
                   href="{{ route('admin.security.overview') }}">
                    <i class="fas fa-chart-line mr-1"></i> Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-xs font-weight-bold {{ request()->routeIs('admin.security.events') ? 'active bg-primary' : 'text-dark' }}" 
                   href="{{ route('admin.security.events') }}">
                    <i class="fas fa-list-alt mr-1"></i> Event Explorer
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-xs font-weight-bold {{ request()->routeIs('admin.security.otp') ? 'active bg-primary' : 'text-dark' }}" 
                   href="{{ route('admin.security.otp') }}">
                    <i class="fas fa-paper-plane mr-1"></i> OTP History
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-xs font-weight-bold {{ request()->routeIs('admin.security.mfa') ? 'active bg-primary' : 'text-dark' }}" 
                   href="{{ route('admin.security.mfa') }}">
                    <i class="fas fa-shield-alt mr-1"></i> MFA Status
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-xs font-weight-bold {{ request()->routeIs('admin.security.logins') ? 'active bg-primary' : 'text-dark' }}" 
                   href="{{ route('admin.security.logins') }}">
                    <i class="fas fa-sign-in-alt mr-1"></i> Login Attempts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-xs font-weight-bold {{ request()->routeIs('admin.security.sessions') ? 'active bg-primary' : 'text-dark' }}" 
                   href="{{ route('admin.security.sessions') }}">
                    <i class="fas fa-satellite-dish mr-1"></i> Active Sessions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-xs font-weight-bold {{ request()->routeIs('admin.security.devices') ? 'active bg-primary' : 'text-dark' }}" 
                   href="{{ route('admin.security.devices') }}">
                    <i class="fas fa-laptop mr-1"></i> Device History
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-xs font-weight-bold {{ request()->routeIs('admin.security.ip') ? 'active bg-danger text-white' : 'text-danger' }}" 
                   href="{{ route('admin.security.ip') }}">
                    <i class="fas fa-search-location mr-1"></i> Investigate IP
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-xs font-weight-bold {{ request()->routeIs('admin.security.alerts') ? 'active bg-warning text-dark' : 'text-dark' }}" 
                   href="{{ route('admin.security.alerts') }}">
                    <i class="fas fa-bell mr-1"></i> Security Alerts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-xs font-weight-bold {{ request()->routeIs('admin.security.policies') ? 'active bg-secondary' : 'text-dark' }}" 
                   href="{{ route('admin.security.policies') }}">
                    <i class="fas fa-sliders-h mr-1"></i> Policies
                </a>
            </li>
        </ul>

        <div>
            <a href="{{ route('admin.security.export', request()->query()) }}" class="btn btn-outline-success btn-sm font-weight-bold text-xs">
                <i class="fas fa-file-export mr-1"></i> Export Audit Log (CSV)
            </a>
        </div>
    </div>
</div>
