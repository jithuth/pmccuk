<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('page_title', 'Dashboard') | {{ $settings['site_name'] ?? 'PMCC-UK' }} Admin</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap">
    <!-- Font Awesome 6 Free -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- AdminLTE 3 for Bootstrap 5 compatible version -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* Restore Font Awesome icon font (must never be overridden) */
        i.fas, i.far, i.fab, i.fal, i.fad, i.nav-icon {
            font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free', 'FontAwesome' !important;
        }
        body, p, span, a, div, td, th, label, input, select, textarea, button {
            font-family: 'Inter', sans-serif;
        }

        /* ── Sidebar ── */
        .main-sidebar,
        .main-sidebar::before { background: #1a2845 !important; width: 260px !important; }
        .sidebar-dark-primary .brand-link { background: #111e35 !important; border-bottom: 1px solid rgba(255,255,255,0.07) !important; }
        .brand-text { font-weight: 900 !important; font-size: 13px !important; letter-spacing: 2px !important; color: #f59e0b !important; }
        .brand-logo-icon {
            width: 30px; height: 30px;
            background: linear-gradient(135deg, #f59e0b, #f97316);
            border-radius: 7px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-right: 8px; flex-shrink: 0;
        }

        /* Nav headers */
        .nav-sidebar .nav-header {
            color: rgba(255,255,255,0.25) !important;
            font-size: 9.5px !important; font-weight: 800 !important;
            letter-spacing: 2px !important; padding: 14px 14px 5px !important;
        }

        /* Nav links */
        .nav-sidebar .nav-link {
            color: rgba(255,255,255,0.58) !important;
            font-size: 13px !important; font-weight: 500 !important;
            border-radius: 9px !important; margin: 2px 8px !important;
            padding: 9px 12px !important;
            transition: all 0.2s ease !important;
        }
        .nav-sidebar .nav-link:hover {
            background: rgba(255,255,255,0.08) !important;
            color: #ffffff !important;
        }
        .nav-sidebar .nav-link:hover .nav-icon { transform: scale(1.2) rotate(-5deg); }
        .nav-sidebar .nav-link.active {
            background: linear-gradient(135deg, #f59e0b, #f97316) !important;
            color: #1a2845 !important; font-weight: 700 !important;
            box-shadow: 0 4px 14px rgba(245,158,11,0.35) !important;
        }
        .nav-sidebar .nav-link.active .nav-icon { color: #1a2845 !important; }
        .nav-sidebar .nav-treeview .nav-link { padding-left: 30px !important; opacity: 0.8; }
        .nav-sidebar .nav-treeview .nav-link:hover { opacity: 1; }

        /* ── Coloured Icons ── */
        .nav-icon { font-size: 15px !important; width: 22px !important; transition: transform 0.22s ease !important; }
        .ic-green  { color: #4ade80 !important; }
        .ic-blue   { color: #38bdf8 !important; }
        .ic-violet { color: #a78bfa !important; }
        .ic-emerald { color: #34d399 !important; }
        .ic-orange { color: #fb923c !important; }
        .ic-pink   { color: #f472b6 !important; }
        .ic-yellow { color: #facc15 !important; }
        .ic-slate  { color: #94a3b8 !important; }

        /* ── Top bar ── */
        .main-header.navbar { border-bottom: 1px solid #e5e7eb !important; box-shadow: 0 1px 8px rgba(0,0,0,0.05) !important; }

        /* ── Content header ── */
        .content-header {
            background: linear-gradient(135deg, #1a2845, #2563eb) !important;
            padding: 16px 24px !important; margin-bottom: 0 !important;
        }
        .content-header h1 { color: #fff !important; font-weight: 800 !important; font-size: 20px !important; margin: 0 !important; }

        /* ── Cards ── */
        .card { border: none !important; border-radius: 12px !important; box-shadow: 0 2px 14px rgba(0,0,0,0.07) !important; }
        .card-header { background: #fff !important; border-bottom: 1px solid #f1f5f9 !important; font-weight: 700 !important; font-size: 12px !important; text-transform: uppercase !important; letter-spacing: 1px !important; border-radius: 12px 12px 0 0 !important; }
        .small-box { border-radius: 12px !important; overflow: hidden !important; }
        .content-wrapper { background: #f4f6f9 !important; }

        /* ── Misc ── */
        .btn { border-radius: 7px !important; font-weight: 600 !important; font-size: 13px !important; }
        .table thead th { font-size: 11px !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; font-weight: 700 !important; }
        .badge { font-weight: 600 !important; }

        /* ── Avatar ── */
        .user-avatar {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #f59e0b, #f97316);
            border-radius: 50%; display: inline-flex;
            align-items: center; justify-content: center;
            font-size: 12px; color: white;
            flex-shrink: 0;
        }
        .right { float: right; }
    </style>

    @yield('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- ── Navbar ── -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ url('/') }}" class="nav-link text-muted" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i> View Live Site
                </a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item">
                <form action="{{ route('admin.logout') }}" method="POST" id="top-logout-form">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger px-3 mr-2 font-weight-bold" title="Sign Out">
                        <i class="fas fa-sign-out-alt"></i> <span class="d-none d-md-inline">Logout</span>
                    </button>
                </form>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#">
                    <div class="user-avatar">
                        <i class="fas fa-user-shield" style="font-size:12px;"></i>
                    </div>
                    <div class="d-none d-md-block text-left lh-sm">
                        <div class="font-weight-bold text-dark" style="font-size:13px;line-height:1.3;">{{ Auth::guard('admin')->user()->username ?? 'Admin' }}</div>
                        <div class="text-muted" style="font-size:10px;">Administrator</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow border-0 p-0 mt-1" style="border-radius:12px;min-width:210px;overflow:hidden;">
                    <div class="px-4 py-3 bg-light border-bottom">
                        <p class="mb-0 font-weight-bold text-dark" style="font-size:13px;">{{ Auth::guard('admin')->user()->username ?? 'Admin' }}</p>
                        <small class="text-muted">System Administrator</small>
                    </div>
                    <form action="{{ route('admin.logout') }}" method="POST" class="p-3">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm btn-block w-100">
                            <i class="fas fa-sign-out-alt mr-2 me-2"></i> Sign Out
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <!-- ── Sidebar ── -->
    <aside class="main-sidebar sidebar-dark-primary elevation-3">
        <!-- Brand -->
        <a href="{{ route('admin.dashboard') }}" class="brand-link d-flex align-items-center text-decoration-none px-3">
            <div class="brand-logo-icon">
                <i class="fas fa-shield-halved text-white" style="font-size:13px;"></i>
            </div>
            <span class="brand-text">PMCC ADMIN</span>
        </a>

        <!-- Sidebar content -->
        <div class="sidebar">
            <nav class="mt-2 pb-3">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ Route::is('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt ic-green"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">Membership</li>

                    <li class="nav-item {{ Route::is('admin.members.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ Route::is('admin.members.*') ? 'active' : '' }}" data-bs-toggle="dropdown">
                            <i class="nav-icon fas fa-users ic-blue"></i>
                            <p>Membership Hub <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item"><a href="{{ route('admin.members.index') }}" class="nav-link {{ Route::is('admin.members.index') ? 'active' : '' }}"><i class="far fa-circle nav-icon ic-blue" style="font-size:7px;"></i><p>All Members</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.members.new') }}" class="nav-link {{ Route::is('admin.members.new') ? 'active' : '' }}"><i class="far fa-circle nav-icon ic-blue" style="font-size:7px;"></i><p>New Registrations</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.members.renewals') }}" class="nav-link {{ Route::is('admin.members.renewals') ? 'active' : '' }}"><i class="far fa-circle nav-icon ic-blue" style="font-size:7px;"></i><p>Renewals</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.members.import') }}" class="nav-link {{ Route::is('admin.members.import') ? 'active' : '' }}"><i class="far fa-circle nav-icon ic-blue" style="font-size:7px;"></i><p>Bulk Import</p></a></li>
                            <li class="nav-item"><a href="{{ route('admin.members.print-card') }}" class="nav-link {{ Route::is('admin.members.print-card') ? 'active' : '' }}"><i class="far fa-circle nav-icon ic-blue" style="font-size:7px;"></i><p>ID Card Generator</p></a></li>
                        </ul>
                    </li>
                    <li class="nav-item"><a href="{{ route('admin.messages') }}" class="nav-link {{ Route::is('admin.messages') ? 'active' : '' }}"><i class="nav-icon fas fa-envelope ic-violet"></i><p>User Inquiries</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.student-requests') }}" class="nav-link {{ Route::is('admin.student-requests') ? 'active' : '' }}"><i class="nav-icon fas fa-graduation-cap ic-violet"></i><p>Student Requests</p></a></li>

                    <li class="nav-header">Content</li>
                    <li class="nav-item"><a href="{{ route('admin.home-banners') }}" class="nav-link {{ Route::is('admin.home-banners') ? 'active' : '' }}"><i class="nav-icon fas fa-film ic-emerald"></i><p>Home Banners</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.about-content') }}" class="nav-link {{ Route::is('admin.about-content') ? 'active' : '' }}"><i class="nav-icon fas fa-info-circle ic-emerald"></i><p>About Content</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.team') }}" class="nav-link {{ Route::is('admin.team') ? 'active' : '' }}"><i class="nav-icon fas fa-user-shield ic-emerald"></i><p>Team & Committee</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.menus') }}" class="nav-link {{ Route::is('admin.menus') ? 'active' : '' }}"><i class="nav-icon fas fa-sitemap ic-emerald"></i><p>Navigation Menus</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.news') }}" class="nav-link {{ Route::is('admin.news') ? 'active' : '' }}"><i class="nav-icon fas fa-newspaper ic-emerald"></i><p>News & Articles</p></a></li>

                    <li class="nav-header">Events</li>
                    <li class="nav-item"><a href="{{ route('admin.events.index') }}" class="nav-link {{ Route::is('admin.events.index') ? 'active' : '' }}"><i class="nav-icon fas fa-calendar-check ic-orange"></i><p>Event Manager</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.events.bookings') }}" class="nav-link {{ Route::is('admin.events.bookings') ? 'active' : '' }}"><i class="nav-icon fas fa-ticket-alt ic-orange"></i><p>Attendance Logs</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.events.fare-logic') }}" class="nav-link {{ Route::is('admin.events.fare-logic') ? 'active' : '' }}"><i class="nav-icon fas fa-tags ic-orange"></i><p>Fare Pricing</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.events.stats') }}" class="nav-link {{ Route::is('admin.events.stats') ? 'active' : '' }}"><i class="nav-icon fas fa-chart-bar ic-orange"></i><p>Event Analytics</p></a></li>

                    <li class="nav-header">Sponsors</li>
                    <li class="nav-item"><a href="{{ route('admin.sponsors.offers') }}" class="nav-link {{ Route::is('admin.sponsors.offers') ? 'active' : '' }}"><i class="nav-icon fas fa-gift ic-pink"></i><p>Sponsor Offers</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.sponsors.redemptions') }}" class="nav-link {{ Route::is('admin.sponsors.redemptions') ? 'active' : '' }}"><i class="nav-icon fas fa-receipt ic-pink"></i><p>Redemptions</p></a></li>

                    <li class="nav-header">Media & Finance</li>
                    <li class="nav-item"><a href="{{ route('admin.gallery') }}" class="nav-link {{ Route::is('admin.gallery') ? 'active' : '' }}"><i class="nav-icon fas fa-image ic-yellow"></i><p>Image Gallery</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.albums') }}" class="nav-link {{ Route::is('admin.albums') ? 'active' : '' }}"><i class="nav-icon fas fa-images ic-yellow"></i><p>Albums</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.videos') }}" class="nav-link {{ Route::is('admin.videos') ? 'active' : '' }}"><i class="nav-icon fas fa-video ic-yellow"></i><p>Videos</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.accounting') }}" class="nav-link {{ Route::is('admin.accounting') ? 'active' : '' }}"><i class="nav-icon fas fa-wallet ic-yellow"></i><p>Financials</p></a></li>

                    <li class="nav-header">System</li>
                    <li class="nav-item"><a href="{{ route('admin.2fa.setup') }}" class="nav-link {{ Route::is('admin.2fa.*') ? 'active' : '' }}"><i class="nav-icon fas fa-qrcode ic-green"></i><p>2FA Security <span class="badge badge-success badge-pill ml-1" style="font-size:9px;">NEW</span></p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.email-settings') }}" class="nav-link {{ Route::is('admin.email-settings') ? 'active' : '' }}"><i class="nav-icon fas fa-at ic-slate"></i><p>Email Settings</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.config.settings') }}" class="nav-link {{ Route::is('admin.config.settings') ? 'active' : '' }}"><i class="nav-icon fas fa-cog ic-slate"></i><p>Global Settings</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.security-audit') }}" class="nav-link {{ Route::is('admin.security-audit') ? 'active' : '' }}"><i class="nav-icon fas fa-shield-alt ic-slate"></i><p>Security Audit</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.access-control') }}" class="nav-link {{ Route::is('admin.access-control') ? 'active' : '' }}"><i class="nav-icon fas fa-user-lock ic-slate"></i><p>Access Control</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.activity-logs') }}" class="nav-link {{ Route::is('admin.activity-logs') ? 'active' : '' }}"><i class="nav-icon fas fa-clock ic-slate"></i><p>Activity Logs</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.config.file-explorer') }}" class="nav-link {{ Route::is('admin.config.file-explorer') ? 'active' : '' }}"><i class="nav-icon fas fa-folder-open ic-slate"></i><p>File Explorer</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.config.system-repair') }}" class="nav-link {{ Route::is('admin.config.system-repair') ? 'active' : '' }}"><i class="nav-icon fas fa-tools ic-slate"></i><p>System Repair</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.config.legal') }}" class="nav-link {{ Route::is('admin.config.legal') ? 'active' : '' }}"><i class="nav-icon fas fa-gavel ic-slate"></i><p>Legal Policy</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.config.db-logs') }}" class="nav-link {{ Route::is('admin.config.db-logs') ? 'active' : '' }}"><i class="nav-icon fas fa-database ic-slate"></i><p>DB Logs</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.config.ip-tool') }}" class="nav-link {{ Route::is('admin.config.ip-tool') ? 'active' : '' }}"><i class="nav-icon fas fa-network-wired ic-slate"></i><p>IP Tool</p></a></li>

                    <li class="nav-header">ACCOUNT</li>
                    <li class="nav-item">
                        <a href="#" class="nav-link text-danger" onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();">
                            <i class="nav-icon fas fa-sign-out-alt"></i>
                            <p>Sign Out Project</p>
                        </a>
                        <form id="sidebar-logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- ── Content Wrapper ── -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <h1>@yield('page_title', 'Dashboard')</h1>
            </div>
        </div>
        <!-- Main content -->
        <section class="content pt-3">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>&copy; {{ date('Y') }} PMCC-UK.</strong> All rights reserved.
        <div class="float-right d-none d-sm-inline-block text-muted">AdminLTE 3 | Bootstrap 5</div>
    </footer>

    <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<!-- jQuery (required for AdminLTE 3) -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<!-- Bootstrap 5 Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE 3.2.0 -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- Global Notifications -->
<script>
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Done!',
            text: @json(session('success')),
            confirmButtonColor: '#1a2845',
            timer: 4000,
            timerProgressBar: true,
            showConfirmButton: false,
            customClass: { popup: 'rounded' }
        });
    @endif
    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: @json(session('error')),
            confirmButtonColor: '#dc3545',
            customClass: { popup: 'rounded' }
        });
    @endif
    @if($errors->any())
        Swal.fire({
            icon: 'warning',
            title: 'Please fix these errors',
            html: `<ul class="text-left mt-2" style="padding-left:1rem">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>`,
            confirmButtonColor: '#f59e0b',
            customClass: { popup: 'rounded' }
        });
    @endif
</script>

@yield('scripts')
</body>
</html>
