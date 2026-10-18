# System Architecture: UK GDPR Privacy Governance & Security Subsystem
**PMCC-UK Laravel Application Platform**  
*Document Version: 1.0.0 | Security Classification: Official-Sensitive*

---

## 1. Executive Summary

This architecture document outlines the design, security controls, and data flow mechanisms implemented to upgrade the **Portsmouth Malayalee Community Club (PMCC-UK)** Laravel platform into an auditable, privacy-by-design membership system compliant with the UK Data Protection Act 2018 and UK GDPR regulations.

The upgrade introduces an additive, non-destructive layer over the existing Laravel application, preserving all member records, event ticketing records, and financial ledger data while adding:
1. Role-Based Access Control (RBAC) with least-privilege separation of duties.
2. An authenticated Member Privacy Center with automated Subject Access Request (SAR) compilation.
3. A centralized Data Protection / Privacy Officer administration portal.
4. An immutable-intent security audit trail with request correlation tracking.
5. Privileged Break-Glass Elevation controls isolating technical developers from raw PII.
6. A 72-hour ICO Personal Data Breach register with containment timeline logging.
7. Automated data retention policy enforcement preserving statutory HMRC accounting records.

---

## 2. Component Architecture

```
+-----------------------------------------------------------------------------------+
|                                 Client Layer                                      |
|  [ Public Web Visitors ]    [ Authenticated Members ]    [ Administrative Staff ] |
+-----------------------------+----------------------------+------------------------+
                              |                            |
                              v                            v
               +------------------------------+  +--------------------------------+
               |  Member Portal & Privacy     |  | AdminLTE 3 Control Panel       |
               |  - Auth (Password / OTP)     |  | - Auth: admin guard + 2FA      |
               |  - Self-Service SAR          |  | - RBAC Policy Enforcement      |
               |  - Rectification & Erasure   |  | - Privacy Officer Dashboard    |
               |  - Consent Center            |  | - Break-Glass Access Gate      |
               +--------------+---------------+  +----------------+---------------+
                              |                                   |
+-----------------------------v-----------------------------------v-----------------+
|                               Middleware Pipeline                                 |
|  SecurityHeaders -> WebApplicationFirewall -> SharedHostingCompat -> AdminRole   |
+---------------------------------------------+-------------------------------------+
                                              |
+---------------------------------------------v-------------------------------------+
|                         Application Core & Services                               |
|  - MemberPolicy / PrivacyRequestPolicy / PrivilegedAccessPolicy / ExportPolicy    |
|  - StatutoryDeadlineCalculator (1-calendar-month + Bank Holiday Rollover)        |
|  - MemberDataExportService (JSON / CSV with 7-Day Expiring One-Time Tokens)      |
|  - SecurityAuditService (Sanitized, append-only correlation audit events)         |
+---------------------------------------------+-------------------------------------+
                                              |
+---------------------------------------------v-------------------------------------+
|                           Data Persistence Layer                                  |
|  - Existing Tables: members, events, event_bookings, financial_transactions       |
|  - Privacy Tables: privacy_requests, items, approvals, documents, status_history   |
|  - Governance: roles, permissions, role_permissions, admin_roles, break-glass     |
|  - Compliance: security_audit_logs, security_incidents, data_retention_policies    |
+-----------------------------------------------------------------------------------+
```

---

## 3. Subsystem Breakdown

### 3.1 Authentication & Boundary Isolation
- **Admin Guard (`auth:admin`):** Governs administrative access to the backend. Protected by 2FA (TOTP RFC 6238) and strict role checks.
- **Member Guard (`auth:member`):** Completely separated session guard using the `members` database provider. Supports both strong password authentication (Bcrypt) and secure One-Time Passcodes (OTP).
- **Public Lockdown:** Dangerous diagnostic maintenance endpoints (`/path-debug`, `/fix-storage`, `/fix-ids`, `/clear-everything`, `/run-migrations`) have been quarantined behind `auth:admin` and `admin.role:superadmin`.
- **API Token Hardening:** Mobile API Bearer tokens utilize cryptographic HMAC SHA-256 signatures tying `member_id`, `guid`, and `timestamp` to `config('app.key')`, preventing token fabrication.

### 3.2 Authorization & Separation of Duties
- **Role Hierarchy:** 9 discrete roles (`superadmin`, `president`, `secretary`, `treasurer`, `membership_admin`, `privacy_officer`, `committee_member`, `developer`, `member`).
- **Policy Enforcement:** All member-facing and administrative endpoints run through Laravel Policies (`MemberPolicy`, `PrivacyRequestPolicy`, `PrivilegedAccessPolicy`, `ExportPolicy`).
- **Anti-Self-Approval:** The system strictly prohibits an administrator from approving their own privileged access request or single-handedly executing high-risk bulk exports without an independent second approver.

### 3.3 Subject Access Request (SAR) Automation & Anti-IDOR
- **SAR Compiler (`MemberDataExportService`):** Aggregates member core details, spouse, emergency contacts, children, event attendance logs, and renewal histories into structured JSON and CSV datasets.
- **Secure Document Storage:** Generated exports are saved to private storage (`storage/app/privacy_exports/`) with randomly generated 64-character tokens.
- **Anti-IDOR:** Document download controller verifies token expiry (7 days) and ensures the authenticated member ID matches the subject of the privacy request before streaming the download.

### 3.4 Audit Trail & Accountability
- **Service (`SecurityAuditService`):** Records actor ID, actor username, action slug, target resource model and ID, descriptive summary, IP address, user agent, outcome (`success`, `failed`, `denied`), and a per-request UUID `correlation_id`.
- **PII Scrubbing:** Passwords, authentication secrets, payment tokens, and credit card numbers are strictly filtered before persistence. Logs are stored in a dedicated `security_audit_logs` table separate from standard application debugging logs.

---

## 4. Dual Database Engine Compatibility
To facilitate continuous automated testing while supporting production hosting:
- **Production:** MySQL 8.x / MariaDB 10.x running on shared or dedicated hosting.
- **Testing:** SQLite `:memory:` utilizing auto-discovered baseline table creation (`2026_04_14_000000_create_core_baseline_tables_if_missing.php`).
- All migrations employ conditional driver checks (`DB::getDriverName() === 'mysql'`) for MySQL-specific DDL operations (`MODIFY COLUMN`), ensuring 100% test compatibility across both engines.
