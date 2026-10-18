<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Auth\Access\HandlesAuthorization;

class MemberPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the actor can view basic member records.
     */
    public function view($actor, Member $member): bool
    {
        // 1. Authenticated Member viewing own profile
        if ($actor instanceof Member) {
            return $actor->id === $member->id;
        }

        // 2. Administrative actor
        if ($actor instanceof Admin) {
            // Developers cannot view member PII without active break-glass grant
            if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
                return false;
            }

            return $actor->hasPermission('members.view.assigned')
                || $actor->hasPermission('members.view.sensitive')
                || $actor->isSuperAdmin();
        }

        return false;
    }

    /**
     * Determine whether the actor can view unredacted sensitive PII.
     */
    public function viewSensitive($actor, Member $member): bool
    {
        if ($actor instanceof Member) {
            return $actor->id === $member->id;
        }

        if ($actor instanceof Admin) {
            if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
                return false;
            }

            return $actor->hasPermission('members.view.sensitive')
                || ($actor->isSuperAdmin() && !$actor->isDeveloper());
        }

        return false;
    }

    /**
     * Determine whether the actor can update the member record.
     */
    public function update($actor, Member $member): bool
    {
        if ($actor instanceof Member) {
            return $actor->id === $member->id;
        }

        if ($actor instanceof Admin) {
            if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
                return false;
            }

            return $actor->hasPermission('members.update') || $actor->isSuperAdmin();
        }

        return false;
    }

    /**
     * Determine whether the actor can export member data.
     */
    public function export($actor): bool
    {
        if ($actor instanceof Admin) {
            if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
                return false;
            }

            // Treasurers cannot export general member PII
            if ($actor->hasRole('treasurer') && !$actor->hasRole(['superadmin', 'privacy_officer', 'secretary'])) {
                return false;
            }

            return $actor->hasPermission('members.export') || $actor->isSuperAdmin();
        }

        return false;
    }

    /**
     * Determine whether the actor can delete/anonymize a member.
     */
    public function delete($actor, Member $member): bool
    {
        // Members cannot delete their own record unilaterally without statutory request
        if ($actor instanceof Admin) {
            if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
                return false;
            }

            return $actor->hasPermission('retention.execute') || $actor->isSuperAdmin();
        }

        return false;
    }
}
