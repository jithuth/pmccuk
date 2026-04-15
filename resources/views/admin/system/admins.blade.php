@extends('layouts.admin')

@section('page_title', 'Access Control')

@section('content')
<div class="row">

    {{-- ── Admin Accounts Table ── --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-lock mr-2 text-danger"></i> Admin Accounts</span>
                <span class="badge badge-secondary">{{ $admins->count() }} total</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>2FA</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($admins as $admin)
                        <tr>
                            <td class="text-muted small">{{ $admin->id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:32px;height:32px;">
                                        <i class="fas fa-user-shield text-dark" style="font-size:12px;"></i>
                                    </div>
                                    <strong>{{ $admin->username }}</strong>
                                </div>
                            </td>
                            <td class="text-muted small">{{ $admin->email ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $admin->role === 'superadmin' ? 'badge-danger' : 'badge-secondary' }}">
                                    {{ ucfirst($admin->role ?? 'admin') }}
                                </span>
                            </td>
                            <td>
                                @if($admin->two_factor_enabled)
                                    <span class="badge badge-success"><i class="fas fa-shield-alt mr-1"></i>Enabled</span>
                                @else
                                    <span class="badge badge-light border text-muted">Off</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    {{-- Reset Password Trigger --}}
                                    <button class="btn btn-outline-info btn-sm" data-toggle="modal" data-target="#resetPass{{ $admin->id }}" title="Reset Password">
                                        <i class="fas fa-key"></i>
                                    </button>

                                    @if($admin->id !== Auth::guard('admin')->id())
                                        <form action="{{ route('admin.access-control.delete', $admin->id) }}" method="POST"
                                              onsubmit="return confirm('Delete admin {{ $admin->username }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete Admin">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge badge-info d-flex align-items-center px-2">Current</span>
                                    @endif
                                </div>

                                {{-- Reset Password Modal --}}
                                <div class="modal fade" id="resetPass{{ $admin->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content" style="border-radius:14px;border:none;overflow:hidden;">
                                            <div class="modal-header bg-info">
                                                <h5 class="modal-title text-white"><i class="fas fa-key mr-2"></i>Reset Password for {{ $admin->username }}</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <form action="{{ route('admin.access-control.update-password', $admin->id) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <div class="modal-body">
                                                    <div class="form-group mb-3">
                                                        <label class="font-weight-bold text-dark">New Password <span class="text-danger">*</span></label>
                                                        <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="font-weight-bold text-dark">Confirm New Password <span class="text-danger">*</span></label>
                                                        <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat new password" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-info btn-sm px-4">
                                                        Update Password
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No admin accounts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Create New Admin ── --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-plus mr-2 text-success"></i> Add New Admin
            </div>
            <div class="card-body">
                <form action="{{ route('admin.access-control.store') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror"
                               value="{{ old('username') }}" placeholder="e.g. john_admin" required>
                        @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Email Address</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" placeholder="admin@pmcc-uk.org">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Role</label>
                        <select name="role" class="form-control">
                            <option value="admin">Admin</option>
                            <option value="superadmin">Super Admin</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               placeholder="Min 8 characters" required>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-group mb-4">
                        <label class="font-weight-bold small">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control"
                               placeholder="Repeat password" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-block w-100">
                        <i class="fas fa-plus mr-2"></i> Create Admin Account
                    </button>
                </form>
            </div>
        </div>

        {{-- 2FA Notice --}}
        <div class="card mt-3 border-0" style="background:linear-gradient(135deg,#1a2845,#2563eb);border-radius:12px;">
            <div class="card-body text-white text-center py-4">
                <i class="fas fa-qrcode fa-2x mb-2 text-warning"></i>
                <h6 class="font-weight-bold mb-1">Enable 2FA</h6>
                <p class="small mb-3 opacity-75">Secure your account with two-factor authentication for extra protection.</p>
                <a href="{{ route('admin.2fa.setup') }}" class="btn btn-warning btn-sm font-weight-bold">
                    <i class="fas fa-shield-alt mr-1"></i> Setup 2FA Now
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
