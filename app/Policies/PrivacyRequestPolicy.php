<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Member;
use App\Models\PrivacyRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrivacyRequestPolicy
{
    use HandlesAuthorization;

    public function view($actor, PrivacyRequest $request): bool
    {
        if ($actor instanceof Member) {
            return $actor->id === $request->member_id;
        }

        if ($actor instanceof Admin) {
            if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
                return false;
            }

            return $actor->hasPermission('privacy.requests.review')
                || $actor->isPrivacyOfficer()
                || $actor->isSuperAdmin();
        }

        return false;
    }

    public function create($actor): bool
    {
        if ($actor instanceof Member) {
            return true;
        }

        if ($actor instanceof Admin) {
            return $actor->hasPermission('privacy.requests.create')
                || $actor->isPrivacyOfficer()
                || $actor->isSuperAdmin();
        }

        return false;
    }

    public function review($actor, PrivacyRequest $request): bool
    {
        if ($actor instanceof Admin) {
            if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
                return false;
            }

            return $actor->hasPermission('privacy.requests.review')
                || $actor->isPrivacyOfficer()
                || $actor->isSuperAdmin();
        }

        return false;
    }

    public function approve($actor, PrivacyRequest $request): bool
    {
        if ($actor instanceof Admin) {
            if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
                return false;
            }

            // Conflict of interest check: cannot approve if actor submitted or is assigned to own interest
            if ($actor->email && strtolower($actor->email) === strtolower($request->requester_email)) {
                return false;
            }

            return $actor->hasPermission('privacy.requests.approve')
                || $actor->isPrivacyOfficer()
                || $actor->isSuperAdmin();
        }

        return false;
    }

    public function fulfil($actor, PrivacyRequest $request): bool
    {
        if ($actor instanceof Admin) {
            if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
                return false;
            }

            return $actor->hasPermission('privacy.requests.fulfil')
                || $actor->isPrivacyOfficer()
                || $actor->isSuperAdmin();
        }

        return false;
    }

    public function close($actor, PrivacyRequest $request): bool
    {
        if ($actor instanceof Admin) {
            if ($actor->isDeveloper() && !$actor->getActivePrivilegedGrant()) {
                return false;
            }

            return $actor->hasPermission('privacy.requests.close')
                || $actor->isPrivacyOfficer()
                || $actor->isSuperAdmin();
        }

        return false;
    }
}
