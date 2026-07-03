@extends('layouts.admin')

@section('page_title', 'Access Control')

@section('content')
    <div class="row">

        {{-- ── Admin Accounts Table ── --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <span class="fw-bold"><i class="fas fa-user-lock mr-2 text-danger"></i> System Administrators</span>
                    <span class="badge bg-secondary rounded-pill">{{ $admins->count() }} active</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light text-uppercase text-secondary small fw-bold">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>2FA</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($admins as $admin)
                                <tr>
                                    <td class="ps-4 text-muted small">#{{ $admin->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-3"
                                                style="width:36px;height:36px;">
                                                <i
                                                    class="fas fa-user-shield text-{{ $admin->role === 'superadmin' ? 'danger' : 'primary' }}"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $admin->username }}</div>
                                                <small class="text-muted">{{ $admin->email ?? 'no-email@pmcc.org' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge rounded-pill {{ $admin->role === 'superadmin' ? 'bg-danger' : ($admin->role === 'staff' ? 'bg-info' : 'bg-primary') }}">
                                            {{ ucfirst($admin->role) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($admin->two_factor_enabled)
                                            <span class="text-success small fw-bold"><i
                                                    class="fas fa-check-circle me-1"></i>Active</span>
                                        @else
                                            <span class="text-muted small">Disabled</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            {{-- Edit Trigger --}}
                                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal"
                                                data-target="#editAdmin{{ $admin->id }}" title="Edit Details">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            {{-- Reset Password Trigger --}}
                                            <button class="btn btn-sm btn-outline-info" data-toggle="modal"
                                                data-target="#resetPass{{ $admin->id }}" title="Change Password">
                                                <i class="fas fa-key"></i>
                                            </button>

                                            @if($admin->id !== Auth::guard('admin')->id())
                                                <form action="{{ route('admin.access-control.delete', $admin->id) }}" method="POST"
                                                    onsubmit="return confirm('WARNING: Are you sure you want to permanently remove {{ $admin->username }}?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="badge bg-light text-dark border ms-2">You</span>
                                            @endif
                                        </div>

                                        {{-- Edit Admin Modal --}}
                                        <div class="modal fade" id="editAdmin{{ $admin->id }}" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg" style="border-radius:15px;">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title font-weight-bold"><i
                                                                class="fas fa-user-edit mr-2"></i>Edit Admin:
                                                            {{ $admin->username }}</h5>
                                                        <button type="button" class="close text-white"
                                                            data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <form action="{{ route('admin.access-control.update', $admin->id) }}"
                                                        method="POST">
                                                        @csrf @method('PATCH')
                                                        <div class="modal-body text-start">
                                                            <div class="form-group mb-3">
                                                                <label class="fw-bold small mb-1">Username</label>
                                                                <input type="text" name="username" class="form-control"
                                                                    value="{{ $admin->username }}" required>
                                                            </div>
                                                            <div class="form-group mb-3">
                                                                <label class="fw-bold small mb-1">Email Address</label>
                                                                <input type="email" name="email" class="form-control"
                                                                    value="{{ $admin->email }}">
                                                            </div>
                                                            <div class="form-group mb-0">
                                                                <label class="fw-bold small mb-1">Access Role</label>
                                                                <select name="role" class="form-control">
                                                                    <option value="staff" {{ $admin->role === 'staff' ? 'selected' : '' }}>Staff (Counter Portal Only)</option>
                                                                    <option value="admin" {{ $admin->role === 'admin' ? 'selected' : '' }}>Admin (Standard Control)</option>
                                                                    <option value="superadmin" {{ $admin->role === 'superadmin' ? 'selected' : '' }}>Super Admin (Master Access)</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer bg-light">
                                                            <button type="button" class="btn btn-link text-muted"
                                                                data-dismiss="modal">Cancel</button>
                                                            <button type="submit"
                                                                class="btn btn-primary px-4 fw-bold shadow-sm">Save
                                                                Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Reset Password Modal --}}
                                        <div class="modal fade" id="resetPass{{ $admin->id }}" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg" style="border-radius:15px;">
                                                    <div class="modal-header bg-dark text-white">
                                                        <h5 class="modal-title font-weight-bold"><i
                                                                class="fas fa-lock mr-2"></i>Change Password</h5>
                                                        <button type="button" class="close text-white"
                                                            data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <form
                                                        action="{{ route('admin.access-control.update-password', $admin->id) }}"
                                                        method="POST">
                                                        @csrf @method('PATCH')
                                                        <div class="modal-body text-start">
                                                            <div class="form-group mb-3">
                                                                <label class="fw-bold small mb-1">New Password</label>
                                                                <div class="input-group">
                                                                    <input type="password" name="password" class="form-control pass-input"
                                                                        placeholder="Min 8 characters" required>
                                                                    <button class="btn btn-outline-secondary toggle-pass" type="button">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="form-group mb-0">
                                                                <label class="fw-bold small mb-1">Confirm New Password</label>
                                                                <div class="input-group">
                                                                    <input type="password" name="password_confirmation"
                                                                        class="form-control pass-input" placeholder="Repeat password" required>
                                                                    <button class="btn btn-outline-secondary toggle-pass" type="button">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer bg-light">
                                                            <button type="button" class="btn btn-link text-muted"
                                                                data-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-dark px-4 fw-bold">Update
                                                                Password</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">No administrator accounts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Create New Admin ── --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-0">
                    <span class="fw-bold text-success"><i class="fas fa-user-plus mr-2"></i> Add New User</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.access-control.store') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="fw-bold small text-muted text-uppercase mb-1" style="font-size:10px;">Username
                                <span class="text-danger">*</span></label>
                            <input type="text" name="username"
                                class="form-control bg-light border-0 py-3 @error('username') is-invalid @enderror"
                                value="{{ old('username') }}" placeholder="e.g. counter_staff_1" required>
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold small text-muted text-uppercase mb-1" style="font-size:10px;">Email
                                Address</label>
                            <input type="email" name="email"
                                class="form-control bg-light border-0 py-3 @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" placeholder="staff@pmccuk.org">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold small text-muted text-uppercase mb-1" style="font-size:10px;">Access
                                Role</label>
                            <select name="role" class="form-select bg-light border-0 h-auto py-3">
                                <option value="staff">Staff (Entrance Counter Only)</option>
                                <option value="admin">Admin (Full Site Control)</option>
                                <option value="superadmin">Super Admin (System Owner)</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="fw-bold small text-muted text-uppercase mb-1" style="font-size:10px;">Initial
                                Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password"
                                    class="form-control bg-light border-0 py-3 @error('password') is-invalid @enderror pass-input"
                                    placeholder="Min 8 characters" style="border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important;" required>
                                <button class="btn btn-light border-0 toggle-pass" type="button" style="border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important;">
                                    <i class="fas fa-eye text-muted"></i>
                                </button>
                            </div>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label class="fw-bold small text-muted text-uppercase mb-1" style="font-size:10px;">Confirm
                                Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" class="form-control bg-light border-0 py-3 pass-input"
                                    placeholder="Repeat password" style="border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important;" required>
                                <button class="btn btn-light border-0 toggle-pass" type="button" style="border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important;">
                                    <i class="fas fa-eye text-muted"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit"
                            class="btn btn-success btn-block w-100 py-3 fw-bold shadow-lg shadow-success/20">
                            <i class="fas fa-plus mr-2"></i> CREATE ACCOUNT
                        </button>
                    </form>
                </div>
            </div>

            {{-- Help Card --}}
            <div class="card mt-3 bg-light border-0">
                <div class="card-body">
                    <h6 class="fw-bold text-dark"><i class="fas fa-info-circle text-primary me-2"></i> Role Guide</h6>
                    <ul class="list-unstyled small text-muted mb-0 space-y-2">
                        <li><strong>Staff:</strong> Only can access the gate entrance tool and QR scanner. No dashboard
                            access.</li>
                        <li><strong>Admin:</strong> Can manage members, content, and bookings.</li>
                        <li><strong>Super Admin:</strong> Full system access including security and access control.</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('scripts')
<script>
    document.querySelectorAll('.toggle-pass').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.closest('.input-group').querySelector('.pass-input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });
</script>
@endsection