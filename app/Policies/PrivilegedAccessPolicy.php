<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\PrivilegedAccessGrant;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrivilegedAccessPolicy
{
    use HandlesAuthorization;

    public function request(Admin $actor): bool
    {
        // Any authenticated administrative account can request break-glass justification
        return true;
    }

    public function grant(Admin $actor, PrivilegedAccessGrant $grant): bool
    {
        // Anti-Self-Approval Rule: Users MUST NOT approve their own privileged elevation
        if ($actor->id === $grant->admin_id) {
            return false;
        }

        // Must possess security.access.grant permission or be SuperAdmin / President / Secretary
        return $actor->hasPermission('security.access.grant')
            || $actor->hasRole(['superadmin', 'president', 'secretary']);
    }

    public function revoke(Admin $actor, PrivilegedAccessGrant $grant): bool
    {
        // Actor can revoke their own grant, or an authorized supervisor can revoke it
        if ($actor->id === $grant->admin_id) {
            return true;
        }

        return $actor->hasPermission('security.access.grant')
            || $actor->isSuperAdmin()
            || $actor->isPrivacyOfficer();
    }
}
