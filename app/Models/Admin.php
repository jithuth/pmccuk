<?php namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';
    public $timestamps = false;
    public $rememberTokenName = null; // legacy table has no remember_token column

    protected $fillable = [
        'username', 'password', 'email', 'role',
        'two_factor_secret', 'two_factor_enabled',
    ];

    protected $hidden = [
        'password', 'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'email' => 'encrypted',
        'two_factor_enabled' => 'boolean',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'admin_roles', 'admin_id', 'role_id');
    }

    public function privilegedGrants()
    {
        return $this->hasMany(PrivilegedAccessGrant::class, 'admin_id');
    }

    public function getActivePrivilegedGrant(): ?PrivilegedAccessGrant
    {
        return $this->privilegedGrants()
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('granted_at')
            ->first();
    }

    public function hasRole(string|array $roles): bool
    {
        $roleList = is_array($roles) ? $roles : [$roles];

        // 1. Check direct role column (legacy compatibility)
        if (in_array($this->role, $roleList)) {
            return true;
        }

        // 2. Check relational RBAC roles
        if ($this->roles->whereIn('slug', $roleList)->isNotEmpty()) {
            return true;
        }

        // 3. Check active break-glass privileged grant
        $activeGrant = $this->getActivePrivilegedGrant();
        if ($activeGrant && in_array($activeGrant->target_role_slug, $roleList)) {
            return true;
        }

        return false;
    }

    public function hasPermission(string $permissionSlug): bool
    {
        // Active break-glass grant permissions
        $activeGrant = $this->getActivePrivilegedGrant();
        if ($activeGrant) {
            $grantRole = Role::where('slug', $activeGrant->target_role_slug)->first();
            if ($grantRole && $grantRole->hasPermission($permissionSlug)) {
                return true;
            }
        }

        // Relational role permissions
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionSlug)) {
                return true;
            }
        }

        // Fallback for legacy superadmin role
        if ($this->role === 'superadmin') {
            // Superadmin has operational permissions except when privacy officer separation is required
            if (!in_array($permissionSlug, ['privacy.requests.approve_own_access'])) {
                return true;
            }
        }

        return false;
    }

    public function isDeveloper(): bool
    {
        return $this->hasRole('developer') || strtolower($this->username) === 'developer';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin' || $this->hasRole('superadmin');
    }

    public function isPrivacyOfficer(): bool
    {
        return $this->hasRole('privacy_officer');
    }
}
