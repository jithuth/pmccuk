<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\DataRetentionPolicy;
use App\Models\PrivacyNotice;

class PrivacyGovernanceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Roles
        $rolesData = [
            [
                'name' => 'Super Administrator',
                'slug' => 'superadmin',
                'description' => 'System supervisor with broad operational administrative capabilities.',
                'is_system' => true,
            ],
            [
                'name' => 'President',
                'slug' => 'president',
                'description' => 'Executive community leader with organizational oversight.',
                'is_system' => true,
            ],
            [
                'name' => 'Secretary',
                'slug' => 'secretary',
                'description' => 'Executive secretary managing official club records and communications.',
                'is_system' => true,
            ],
            [
                'name' => 'Treasurer',
                'slug' => 'treasurer',
                'description' => 'Chief financial officer with accounting, payment, and financial audit responsibilities.',
                'is_system' => true,
            ],
            [
                'name' => 'Membership Administrator',
                'slug' => 'membership_admin',
                'description' => 'Manages membership onboarding, renewals, and card verifications.',
                'is_system' => true,
            ],
            [
                'name' => 'Privacy Officer',
                'slug' => 'privacy_officer',
                'description' => 'Designated Data Protection / Privacy Officer governing UK GDPR compliance, SARs, and erasure workflows.',
                'is_system' => true,
            ],
            [
                'name' => 'Committee Member',
                'slug' => 'committee_member',
                'description' => 'Executive committee member with assigned community duties.',
                'is_system' => true,
            ],
            [
                'name' => 'Developer',
                'slug' => 'developer',
                'description' => 'Technical software engineer. Strictly isolated from member personal data by default.',
                'is_system' => true,
            ],
            [
                'name' => 'Member',
                'slug' => 'member',
                'description' => 'Standard community member accessing the self-service Privacy Center.',
                'is_system' => true,
            ],
        ];

        $roles = [];
        foreach ($rolesData as $r) {
            $roles[$r['slug']] = Role::updateOrCreate(['slug' => $r['slug']], $r);
        }

        // 2. Seed Granular Permissions
        $permissionsData = [
            ['name' => 'View Own Member Data', 'slug' => 'members.view.own', 'category' => 'members', 'description' => 'Can view authenticated member profile and family records.'],
            ['name' => 'View Assigned Member Data', 'slug' => 'members.view.assigned', 'category' => 'members', 'description' => 'Can view basic member information for assigned operational duties.'],
            ['name' => 'View Sensitive Member Data', 'slug' => 'members.view.sensitive', 'category' => 'members', 'description' => 'Can view unredacted sensitive member fields (medical/emergency/DOB).'],
            ['name' => 'Update Member Profiles', 'slug' => 'members.update', 'category' => 'members', 'description' => 'Can modify member profile details upon verified request.'],
            ['name' => 'Export Member Data', 'slug' => 'members.export', 'category' => 'members', 'description' => 'Can initiate authorized bulk member exports.'],
            ['name' => 'Export Sensitive Data', 'slug' => 'members.export.sensitive', 'category' => 'members', 'description' => 'Can export sensitive PII requiring secondary privacy approval.'],
            ['name' => 'Create Privacy Request', 'slug' => 'privacy.requests.create', 'category' => 'privacy', 'description' => 'Can submit a Subject Access, Rectification, or Erasure request.'],
            ['name' => 'Review Privacy Request', 'slug' => 'privacy.requests.review', 'category' => 'privacy', 'description' => 'Can review privacy request evidence and conduct discovery.'],
            ['name' => 'Approve Privacy Request', 'slug' => 'privacy.requests.approve', 'category' => 'privacy', 'description' => 'Can independently approve high-risk SAR/erasure decisions.'],
            ['name' => 'Fulfil Privacy Request', 'slug' => 'privacy.requests.fulfil', 'category' => 'privacy', 'description' => 'Can generate secure data packages or execute selective erasure.'],
            ['name' => 'Close Privacy Request', 'slug' => 'privacy.requests.close', 'category' => 'privacy', 'description' => 'Can formally close a statutory privacy request.'],
            ['name' => 'View Audit Logs', 'slug' => 'audit.logs.view', 'category' => 'security', 'description' => 'Can view security audit logs and privileged access events.'],
            ['name' => 'Manage Security Sessions', 'slug' => 'security.sessions.manage', 'category' => 'security', 'description' => 'Can inspect active sessions and revoke compromised credentials.'],
            ['name' => 'Grant Privileged Access', 'slug' => 'security.access.grant', 'category' => 'security', 'description' => 'Can approve time-limited Break-Glass emergency access requests.'],
            ['name' => 'Manage Security Incidents', 'slug' => 'security.incidents.manage', 'category' => 'security', 'description' => 'Can record, evaluate ICO thresholds, and manage data breach investigations.'],
            ['name' => 'Manage Retention Policies', 'slug' => 'retention.policies.manage', 'category' => 'retention', 'description' => 'Can configure statutory and operational retention rules.'],
            ['name' => 'Execute Data Disposal', 'slug' => 'retention.execute', 'category' => 'retention', 'description' => 'Can execute authorized data retention disposal and anonymization sweeps.'],
            ['name' => 'View Financial Records', 'slug' => 'financials.view', 'category' => 'financials', 'description' => 'Can view club financial transactions and reconciliations.'],
            ['name' => 'Export Financial Ledgers', 'slug' => 'financials.export', 'category' => 'financials', 'description' => 'Can export accounting records for statutory tax compliance.'],
        ];

        $perms = [];
        foreach ($permissionsData as $p) {
            $perms[$p['slug']] = Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // 3. Assign Role Permissions (Principle of Least Privilege)
        
        // Superadmin: Broad administrative rights (excluding self-approval bypass)
        $roles['superadmin']->permissions()->sync(array_values(array_map(fn($p) => $p->id, $perms)));

        // Privacy Officer: Data protection, SARs, audits, retention, incidents
        $privacyOfficerPerms = [
            'members.view.sensitive', 'members.view.assigned',
            'privacy.requests.create', 'privacy.requests.review', 'privacy.requests.approve',
            'privacy.requests.fulfil', 'privacy.requests.close',
            'audit.logs.view', 'security.incidents.manage',
            'retention.policies.manage', 'retention.execute',
        ];
        $roles['privacy_officer']->permissions()->sync(
            array_filter(array_map(fn($slug) => $perms[$slug]->id ?? null, $privacyOfficerPerms))
        );

        // Treasurer: Financials, reconciliation, assigned members (No general PII export)
        $treasurerPerms = [
            'financials.view', 'financials.export',
            'members.view.assigned', 'audit.logs.view',
        ];
        $roles['treasurer']->permissions()->sync(
            array_filter(array_map(fn($slug) => $perms[$slug]->id ?? null, $treasurerPerms))
        );

        // Membership Admin: Membership onboarding, renewals, basic member updates
        $membershipAdminPerms = [
            'members.view.assigned', 'members.update',
            'privacy.requests.create',
        ];
        $roles['membership_admin']->permissions()->sync(
            array_filter(array_map(fn($slug) => $perms[$slug]->id ?? null, $membershipAdminPerms))
        );

        // President & Secretary: High-level organizational governance
        $execPerms = [
            'members.view.assigned', 'members.view.sensitive',
            'privacy.requests.review', 'security.access.grant',
            'audit.logs.view', 'financials.view',
        ];
        $roles['president']->permissions()->sync(
            array_filter(array_map(fn($slug) => $perms[$slug]->id ?? null, $execPerms))
        );
        $roles['secretary']->permissions()->sync(
            array_filter(array_map(fn($slug) => $perms[$slug]->id ?? null, $execPerms))
        );

        // Developer: Technical logs only; strictly NO member PII access by default!
        $devPerms = [
            'audit.logs.view',
        ];
        $roles['developer']->permissions()->sync(
            array_filter(array_map(fn($slug) => $perms[$slug]->id ?? null, $devPerms))
        );

        // Member: Self-service own data and privacy request creation
        $memberPerms = [
            'members.view.own',
            'privacy.requests.create',
        ];
        $roles['member']->permissions()->sync(
            array_filter(array_map(fn($slug) => $perms[$slug]->id ?? null, $memberPerms))
        );

        // 4. Seed Data Retention Policies
        $retentionPolicies = [
            [
                'data_category' => 'membership_records',
                'purpose' => 'Community membership administration, communications, and digital identity management.',
                'lawful_basis' => 'Contractual Necessity (Art. 6(1)(b) UK GDPR) & Legitimate Interest (Art. 6(1)(f))',
                'retention_period_days' => 730, // 2 years post-expiry
                'retention_trigger' => 'membership_expiry',
                'responsible_owner' => 'Privacy Officer',
                'disposal_action' => 'anonymize',
                'legal_hold' => false,
            ],
            [
                'data_category' => 'family_records',
                'purpose' => 'Family membership administration and minor dependent event safety.',
                'lawful_basis' => 'Contractual Necessity & Vital Interests / Safeguarding',
                'retention_period_days' => 730,
                'retention_trigger' => 'membership_expiry',
                'responsible_owner' => 'Privacy Officer',
                'disposal_action' => 'anonymize',
                'legal_hold' => false,
            ],
            [
                'data_category' => 'registration_forms',
                'purpose' => 'Verification of initial community membership application and constitution agreement.',
                'lawful_basis' => 'Contractual Necessity & Consent where applicable',
                'retention_period_days' => 730,
                'retention_trigger' => 'membership_expiry',
                'responsible_owner' => 'Membership Administrator',
                'disposal_action' => 'archive',
                'legal_hold' => false,
            ],
            [
                'data_category' => 'financial_transactions',
                'purpose' => 'Statutory accounting, HMRC tax compliance, and financial transparency.',
                'lawful_basis' => 'Legal Obligation (Art. 6(1)(c) UK GDPR; UK Taxes Management Act 1970)',
                'retention_period_days' => 2190, // 6 years
                'retention_trigger' => 'created_at',
                'responsible_owner' => 'Treasurer',
                'disposal_action' => 'archive',
                'legal_hold' => true,
                'legal_hold_reason' => 'UK HMRC statutory minimum 6-year accounting records retention mandate. Right to Erasure does not apply.',
            ],
            [
                'data_category' => 'payment_history',
                'purpose' => 'Individual membership fee payment receipts and subscription verification.',
                'lawful_basis' => 'Legal Obligation & Contractual Necessity',
                'retention_period_days' => 2190,
                'retention_trigger' => 'created_at',
                'responsible_owner' => 'Treasurer',
                'disposal_action' => 'archive',
                'legal_hold' => true,
                'legal_hold_reason' => 'UK HMRC statutory audit trail requirement.',
            ],
            [
                'data_category' => 'privacy_requests',
                'purpose' => 'UK GDPR statutory compliance accountability evidence for ICO supervisory review.',
                'lawful_basis' => 'Legal Obligation (Art. 5(2) & Articles 12-23 UK GDPR)',
                'retention_period_days' => 1095, // 3 years post-closure
                'retention_trigger' => 'request_closed',
                'responsible_owner' => 'Privacy Officer',
                'disposal_action' => 'archive',
                'legal_hold' => false,
            ],
            [
                'data_category' => 'audit_logs',
                'purpose' => 'Application cyber-security monitoring, forensic investigation, and breach accountability.',
                'lawful_basis' => 'Legal Obligation & Legitimate Interest (Security Defence)',
                'retention_period_days' => 730, // 2 years
                'retention_trigger' => 'created_at',
                'responsible_owner' => 'Privacy Officer',
                'disposal_action' => 'archive',
                'legal_hold' => false,
            ],
            [
                'data_category' => 'event_bookings',
                'purpose' => 'Cultural event ticketing, venue capacity control, and attendee safety check-in.',
                'lawful_basis' => 'Contractual Necessity & Health & Safety Regulations',
                'retention_period_days' => 365, // 1 year post-event
                'retention_trigger' => 'event_date',
                'responsible_owner' => 'Event Coordinator',
                'disposal_action' => 'anonymize',
                'legal_hold' => false,
            ],
        ];

        foreach ($retentionPolicies as $pol) {
            DataRetentionPolicy::updateOrCreate(['data_category' => $pol['data_category']], $pol);
        }

        // 5. Seed Versioned Privacy Notice
        $noticeContent = <<<MARKDOWN
# PMCC-UK Privacy Notice & Data Governance Policy
**Version:** 1.0  
**Effective Date:** 18 October 2026  
**Data Controller:** Plymouth Malayalee Community Club (PMCC-UK)  
**Contact:** privacy@pmcc-uk.org  

## 1. Introduction
The Plymouth Malayalee Community Club (PMCC-UK) respects your privacy and is committed to protecting your personal data in accordance with the UK General Data Protection Regulation (UK GDPR) and the Data Protection Act 2018.

## 2. Lawful Bases for Processing
We process personal data under clearly defined lawful bases:
- **Contractual Necessity (Art. 6(1)(b))**: Administering your club membership, issuing digital membership cards, managing renewal applications, and processing event tickets.
- **Legal Obligation (Art. 6(1)(c))**: Retaining accounting ledgers, payment histories, and donation records for UK HMRC tax and company statutory compliance (minimum 6 years).
- **Legitimate Interests (Art. 6(1)(f))**: Safeguarding community security, defending against cyber threats via firewall logging, and maintaining accurate historical archives.
- **Consent (Art. 6(1)(a))**: Optional marketing emails, community WhatsApp broadcasts, and promotional photography at cultural celebrations. Consent may be withdrawn at any time.

## 3. Your Statutory Rights
Under UK GDPR, you have the right to:
1. **Access (SAR)**: Request a copy of your personal data and original registration records held by PMCC-UK.
2. **Rectification**: Request prompt correction of inaccurate or incomplete personal information.
3. **Erasure ("Right to be Forgotten")**: Request erasure of personal data where retention is no longer justified. *Note: Data erasure does not automatically cancel active membership, and statutory financial records cannot be erased under HMRC obligations.*
4. **Restriction & Objection**: Request restriction or object to specific processing activities.
5. **Data Portability**: Receive your personal data in a structured, commonly used CSV or JSON format.

## 4. Subject Access & Rights Submission
Members may exercise their statutory rights directly through the authenticated **Member Privacy Center** (`/member/privacy`) or by submitting a formal request to our designated Privacy Officer. We respond within the statutory deadline of one calendar month without charge.
MARKDOWN;

        PrivacyNotice::updateOrCreate(
            ['version' => '1.0'],
            [
                'title' => 'PMCC-UK Official Privacy Notice',
                'content_markdown' => $noticeContent,
                'summary_of_changes' => 'Initial comprehensive UK GDPR Privacy Notice with Member Privacy Center integration.',
                'published_at' => now(),
                'is_active' => true,
            ]
        );
    }
}
