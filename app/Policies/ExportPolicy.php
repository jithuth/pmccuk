<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExportPolicy
{
    use HandlesAuthorization;

    public function exportMembers(Admin $actor): bool
    {
        if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
            return false;
        }

        if ($actor->hasRole('treasurer') && !$actor->hasRole(['superadmin', 'privacy_officer', 'secretary'])) {
            return false;
        }

        return $actor->hasPermission('members.export') || $actor->isSuperAdmin();
    }

    public function exportSensitive(Admin $actor): bool
    {
        if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
            return false;
        }

        return $actor->hasPermission('members.export.sensitive')
            || $actor->isPrivacyOfficer()
            || $actor->isSuperAdmin();
    }

    public function exportFinancials(Admin $actor): bool
    {
        return $actor->hasPermission('financials.export')
            || $actor->hasRole(['treasurer', 'president', 'superadmin']);
    }
}
