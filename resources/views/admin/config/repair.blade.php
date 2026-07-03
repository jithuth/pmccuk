@extends('layouts.admin')

@section('page_title', 'System Maintenance & Repair')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="card-title fw-bold mb-0 text-primary"><i class="fas fa-tools me-2"></i> System Diagnostics & Recovery</h5>
                <p class="text-muted small mb-0 mt-1">Diagnose common host environment anomalies, refresh cached architectures, and execute core repairs.</p>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- OPTIMIZE CLEAR -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border rounded-3 p-3 shadow-sm hover-shadow transition-all">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary-subtle text-primary rounded-circle p-3 me-3">
                                    <i class="fas fa-bolt fa-lg"></i>
                                </div>
                                <h6 class="fw-bold mb-0">Clear All Cache</h6>
                            </div>
                            <p class="text-muted small flex-grow-1">Clears route, config, view, and general application caches. Recommended if UI modifications are not reflecting.</p>
                            <form action="{{ route('admin.config.system-repair.run') }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="optimize">
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-bold">Run optimize:clear</button>
                            </form>
                        </div>
                    </div>

                    <!-- STORAGE LINK -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border rounded-3 p-3 shadow-sm hover-shadow transition-all">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info-subtle text-info rounded-circle p-3 me-3">
                                    <i class="fas fa-link fa-lg"></i>
                                </div>
                                <h6 class="fw-bold mb-0">Regenerate Symlink</h6>
                            </div>
                            <p class="text-muted small flex-grow-1">Re-establishes the symbolic link from <code>public/storage</code> to <code>storage/app/public</code>. Fixes broken image uploads.</p>
                            <form action="{{ route('admin.config.system-repair.run') }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="storage">
                                <button type="submit" class="btn btn-outline-info btn-sm w-100 rounded-pill fw-bold">Run storage:link</button>
                            </form>
                        </div>
                    </div>

                    <!-- VIEW CLEAR -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border rounded-3 p-3 shadow-sm hover-shadow transition-all">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-secondary-subtle text-secondary rounded-circle p-3 me-3">
                                    <i class="fas fa-eye fa-lg"></i>
                                </div>
                                <h6 class="fw-bold mb-0">Clear View Cache</h6>
                            </div>
                            <p class="text-muted small flex-grow-1">Purges all compiled Blade templates. Forces the server to recompile view files on next load.</p>
                            <form action="{{ route('admin.config.system-repair.run') }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="view">
                                <button type="submit" class="btn btn-outline-secondary btn-sm w-100 rounded-pill fw-bold">Run view:clear</button>
                            </form>
                        </div>
                    </div>

                    <!-- APP CACHE CLEAR -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border rounded-3 p-3 shadow-sm hover-shadow transition-all">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success-subtle text-success rounded-circle p-3 me-3">
                                    <i class="fas fa-database fa-lg"></i>
                                </div>
                                <h6 class="fw-bold mb-0">Application Cache</h6>
                            </div>
                            <p class="text-muted small flex-grow-1">Flushes the general database-driven or file-driven application cache without clearing configuration settings.</p>
                            <form action="{{ route('admin.config.system-repair.run') }}" method="POST">
                                @csrf
                                <input type="hidden" name="action" value="cache">
                                <button type="submit" class="btn btn-outline-success btn-sm w-100 rounded-pill fw-bold">Run cache:clear</button>
                            </form>
                        </div>
                    </div>

                    <!-- DATABASE MIGRATIONS -->
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border rounded-3 p-3 shadow-sm hover-shadow transition-all">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-danger-subtle text-danger rounded-circle p-3 me-3">
                                    <i class="fas fa-table fa-lg"></i>
                                </div>
                                <h6 class="fw-bold mb-0">Run Migrations</h6>
                            </div>
                            <p class="text-muted small flex-grow-1">Executes any pending database schema updates safely using the force flag. Requires high privileges.</p>
                            @if(auth('admin')->user()->username === 'superadmin')
                                <form action="{{ route('admin.config.system-repair.run') }}" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to run database migrations on production database?')">
                                    @csrf
                                    <input type="hidden" name="action" value="migrate">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold">Run migrate --force</button>
                                </form>
                            @else
                                <button type="button" class="btn btn-light btn-sm w-100 rounded-pill fw-bold text-muted" disabled>
                                    <i class="fas fa-lock me-1"></i> SuperAdmin Only
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
