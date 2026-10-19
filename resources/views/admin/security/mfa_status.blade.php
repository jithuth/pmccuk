@extends('layouts.admin')

@section('page_title', 'MFA Enrollment Status')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-0">
                <i class="fas fa-shield-alt text-primary mr-2"></i>Multi-Factor Authentication (MFA) Status
            </h1>
            <p class="text-muted small mb-0">Monitor TOTP authenticator adoption across members and administrative staff.</p>
        </div>
    </div>

    @include('admin.security._nav')

    <!-- Metrics Cards -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3" style="border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-muted text-uppercase">Member MFA Adoption</div>
                        <div class="h4 font-weight-bold text-dark mt-1">
                            {{ $memberStats['enabled'] }} / {{ $memberStats['total'] }}
                            <small class="text-xs text-muted">({{ $memberStats['total'] > 0 ? round(($memberStats['enabled'] / $memberStats['total']) * 100, 1) : 0 }}%)</small>
                        </div>
                    </div>
                    <span class="badge badge-info p-2"><i class="fas fa-user-check fa-2x"></i></span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3" style="border-radius:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-muted text-uppercase">Admin MFA Adoption (Mandatory)</div>
                        <div class="h4 font-weight-bold text-dark mt-1">
                            {{ $adminStats['enabled'] }} / {{ $adminStats['total'] }}
                            <small class="text-xs text-muted">({{ $adminStats['total'] > 0 ? round(($adminStats['enabled'] / $adminStats['total']) * 100, 1) : 0 }}%)</small>
                        </div>
                    </div>
                    <span class="badge badge-warning p-2"><i class="fas fa-user-shield fa-2x"></i></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs: Members vs Admins -->
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-header bg-white p-2 border-0">
            <ul class="nav nav-tabs card-header-tabs ml-0">
                <li class="nav-item">
                    <a class="nav-link font-weight-bold text-xs {{ $tab === 'members' ? 'active' : 'text-muted' }}" 
                       href="{{ route('admin.security.mfa', ['tab' => 'members']) }}">
                        <i class="fas fa-users mr-1"></i> Community Members ({{ $memberStats['total'] }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link font-weight-bold text-xs {{ $tab === 'admins' ? 'active' : 'text-muted' }}" 
                       href="{{ route('admin.security.mfa', ['tab' => 'admins']) }}">
                        <i class="fas fa-user-shield mr-1"></i> Administrators ({{ $adminStats['total'] }})
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.security.mfa') }}" class="row g-2 align-items-center text-xs mb-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="col-md-4">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, ID, or email..." class="form-control form-control-sm">
                </div>
                @if($tab === 'members')
                    <div class="col-md-3">
                        <select name="mfa_state" class="form-control form-control-sm">
                            <option value="">All MFA States</option>
                            <option value="enabled" {{ request('mfa_state') === 'enabled' ? 'selected' : '' }}>MFA Enabled Only</option>
                            <option value="disabled" {{ request('mfa_state') === 'disabled' ? 'selected' : '' }}>MFA Disabled Only</option>
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold">Search</button>
                    <a href="{{ route('admin.security.mfa', ['tab' => $tab]) }}" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover mb-0 text-xs">
                    <thead class="bg-light text-muted uppercase">
                        <tr>
                            <th class="px-3 py-2">Account</th>
                            <th class="py-2">Contact</th>
                            <th class="py-2">MFA Status</th>
                            <th class="py-2">Enrolled / Confirmed At</th>
                            <th class="py-2">Emergency Recovery Codes</th>
                            <th class="py-2 text-right pr-3">Privacy Protection</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @if($tab === 'members' && $members)
                            @forelse($members as $m)
                                <tr>
                                    <td class="px-3 py-2.5">
                                        <div class="font-weight-bold text-dark">{{ $m->full_name }}</div>
                                        <div class="text-muted text-[10px]">ID: {{ $m->membership_id_assigned ?: 'PMCC-' . $m->id }}</div>
                                    </td>
                                    <td class="py-2.5">
                                        <div>{{ $m->email ?: 'No email' }}</div>
                                        <div class="text-muted text-[10px]">{{ $m->mobile_number }}</div>
                                    </td>
                                    <td class="py-2.5">
                                        @if($m->two_factor_enabled)
                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Active (TOTP)</span>
                                        @else
                                            <span class="badge badge-secondary px-2 py-1">Disabled</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-muted">
                                        {{ $m->two_factor_confirmed_at?->format('Y-m-d H:i:s') ?? '—' }}
                                    </td>
                                    <td class="py-2.5">
                                        @if($m->two_factor_enabled && !empty($m->two_factor_recovery_codes))
                                            @php
                                                $codes = json_decode($m->two_factor_recovery_codes, true);
                                                $avail = is_array($codes) ? count(array_filter($codes, fn($c) => empty($c['used']))) : 0;
                                            @endphp
                                            <span class="badge badge-light border">{{ $avail }} codes available</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-right pr-3 text-muted">
                                        <i class="fas fa-lock text-success" title="Secrets encrypted; zero-knowledge access"></i> Protected
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No members found.</td></tr>
                            @endforelse
                        @elseif($tab === 'admins' && $admins)
                            @forelse($admins as $a)
                                <tr>
                                    <td class="px-3 py-2.5">
                                        <div class="font-weight-bold text-dark">{{ $a->username }}</div>
                                        <span class="badge badge-dark text-[10px]">{{ strtoupper($a->role) }}</span>
                                    </td>
                                    <td class="py-2.5">
                                        {{ $a->email ?: 'No email configured' }}
                                    </td>
                                    <td class="py-2.5">
                                        @if($a->two_factor_enabled)
                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-shield-alt mr-1"></i> Active (TOTP)</span>
                                        @else
                                            <span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i> Pending Setup</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-muted">
                                        {{ $a->two_factor_confirmed_at?->format('Y-m-d H:i:s') ?? ($a->two_factor_enabled ? 'Active' : '—') }}
                                    </td>
                                    <td class="py-2.5">
                                        @if($a->two_factor_enabled && !empty($a->two_factor_recovery_codes))
                                            @php
                                                $codes = json_decode($a->two_factor_recovery_codes, true);
                                                $avail = is_array($codes) ? count(array_filter($codes, fn($c) => empty($c['used']))) : 0;
                                            @endphp
                                            <span class="badge badge-light border">{{ $avail }} codes available</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-right pr-3 text-muted">
                                        <i class="fas fa-lock text-success" title="Secrets encrypted; zero-knowledge access"></i> Protected
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No administrators found.</td></tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                @if($tab === 'members' && $members)
                    <div class="text-xs text-muted">Showing {{ $members->firstItem() ?? 0 }} to {{ $members->lastItem() ?? 0 }} of {{ $members->total() }} records</div>
                    {{ $members->links() }}
                @elseif($tab === 'admins' && $admins)
                    <div class="text-xs text-muted">Showing {{ $admins->firstItem() ?? 0 }} to {{ $admins->lastItem() ?? 0 }} of {{ $admins->total() }} records</div>
                    {{ $admins->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
