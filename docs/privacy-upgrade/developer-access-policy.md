# Developer Access & Privileged Operations Policy
**Portsmouth Malayalee Community Club (PMCC-UK)**  
*Document Version: 1.0.0 | Information Security & Vendor Governance*

---

## 1. Purpose & Scope

This policy governs access rights, credentials, environment segregation, and emergency procedures for software developers, system architects, external technical contractors, and third-party IT support personnel working on the PMCC Laravel codebase and infrastructure.

Technical authority to build, deploy, or maintain software does **not** grant authorization to view, alter, or extract member personal information.

---

## 2. Mandatory Security Rules

### 2.1 Individual Named Accounts
- Shared administrative credentials (e.g. `admin@pmcc.uk` with a shared password) are **strictly prohibited**.
- Every developer must authenticate using an individual named account with Multi-Factor Authentication (MFA / 2FA) enforced.

### 2.2 Default Prohibition of PII Access
- Developer accounts in production carry the `developer` role.
- By default, the `developer` role has **zero** access permissions to member profiles, family dependent data, registration form uploads, or financial ledger exports.
- Laravel policies (`MemberPolicy`, `ExportPolicy`) actively block requests originating from developers attempting to access confidential endpoints.

### 2.3 Sanitized Development & Staging Datasets
- Live production databases must **never** be downloaded to local developer workstations, personal laptops, or unsecured staging servers.
- Local and staging environments must use synthetic seed data (`PrivacyGovernanceSeeder`, database factories) or irreversibly sanitized/obfuscated database dumps.

### 2.4 Emergency "Break-Glass" Elevation Procedure
In the event of an urgent production outage or data integrity defect requiring developer access:
1. The developer must submit a formal **Privileged Access Grant** request via `/admin/privacy/access`.
2. The request must include:
   - Target elevated role required (e.g. `privacy_officer` or `membership_admin`)
   - Specific ticket reference and justification
   - Requested duration (maximum 24 hours, recommended 2 to 4 hours)
3. **Anti-Self-Approval Enforcement:** The system strictly prohibits the developer from approving their own elevation. The request must be independently approved by the President, Secretary, or Super Administrator.
4. An automated emergency alert is dispatched to the Executive Committee via Telegram / Email upon request and approval.
5. All actions performed during the elevated session are tagged with the grant ID and logged to the immutable security audit trail.
6. The elevation automatically expires at the designated timestamp, revoking elevated permissions without requiring manual intervention.

---

## 3. Organizational Ownership & Secret Management
- PMCC-UK retains sole executive ownership of all domain names (`pmcc.org.uk`), source code repositories (GitHub organization), production hosting accounts, and DNS records.
- Production API keys, mail credentials, and database passwords must reside in production `.env` files protected by restrictive filesystem permissions (`chmod 600`), never committed to Git version control.
