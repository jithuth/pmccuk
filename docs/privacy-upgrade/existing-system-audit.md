# Existing System Audit & Architectural Baseline
**PMCC-UK Laravel Application – UK GDPR, Privacy Governance & Security Upgrade**
**Audit Date:** October 2026  
**Auditor:** Principal Laravel Architect & Application Security Engineer  

---

## 1. Executive Summary

This document presents a comprehensive, production-grade audit of the active **PMCC-UK (Plymouth Malayalee Community Club)** web application. The platform is an active, production-grade membership management, cultural events, ticketing, and communications system deployed primarily on a shared Linux/cPanel hosting environment (Hostinger, user identifier `u601819832`) with a local Windows development environment.

The application possesses robust operational modules (member registration, renewal pipelines, event booking, digital ID card generation, financial reconciliation, and WhatsApp/Telegram notification bridges). However, from a **UK GDPR, Data Protection Act 2018, and modern cyber-security governance perspective**, several structural deficiencies and architectural vulnerabilities exist that require systematic remediation:
- Absence of fine-grained Role-Based Access Control (RBAC); authorization relies on monolithic checks (`superadmin`, `admin`, `staff`).
- Lack of a dedicated, authenticated self-service **Member Privacy Center** where members can exercise statutory rights (Subject Access Requests, Rectification, Erasure, Objection, Restriction, and Portability).
- Insecure diagnostic and database repair routes exposed without authentication (e.g., `/fix-ids`, `/run-migrations`, `/fix-storage`).
- Unregulated data exports (CSV and PDF lists containing unrestricted personal data without approval gates or justification logging).
- Mixed data encryption implementations (custom `HasSmartDecryption` trait and encrypted casts without key rotation or strict purpose limitations).
- Developer and administrative accounts have broad direct access to raw member personally identifiable information (PII).

---

## 2. Technology Stack & Runtime Environment

| Component | Identified Version / Specification | Details & Observations |
| :--- | :--- | :--- |
| **PHP Runtime** | PHP 8.2.12 (CLI / FPM) | Modern PHP 8.2 features in use (match expressions, typed properties, sensitive parameter attributes). |
| **Framework** | Laravel Framework 12.56.0 | Latest Laravel 12 release utilizing `bootstrap/app.php` single-file configuration syntax. |
| **Database Engine** | MySQL (Production: `u601819832_pmcc_v2`) / SQLite (Testing) | Production uses MySQL 8 / MariaDB on Hostinger. Automated testing uses SQLite `:memory:`. |
| **Frontend Frameworks** | Admin: AdminLTE 3.2.0 + Bootstrap 5.3.2<br>Public: Tailwind CSS v4 + Vite 7 | Public layout compiles via Vite with Tailwind CSS v4; Admin uses AdminLTE 3 / Bootstrap 5 and FontAwesome 6. |
| **Auth & Security Packages** | `laravel/sanctum` (^4.3)<br>`pragmarx/google2fa-laravel` (^3.0) | Multi-factor authentication (TOTP) implemented for `Admin` accounts via Google2FA. |
| **Document Generation** | `barryvdh/laravel-dompdf` (^3.1) | Used for generating PDF attendee lists and printable membership ID cards. |
| **External Messaging** | Telegram Bot API, OpenWA WhatsApp Daemon | Synchronous notifications to Telegram and background WhatsApp broadcasts via local Node.js daemon. |

---

## 3. Module & Architectural Inventory

### 3.1 Authentication & Authorization
- **Admin Guard (`auth:admin`)**: Backed by `App\Models\Admin`. Supported roles in database: `superadmin`, `admin`, `staff`, `sponsor`.
- **Middleware Controls**:
  - `AdminRole`: Enforces role matching (e.g. `admin.role:admin,superadmin`). Redirects `staff` to gate check-in dashboard.
  - `TwoFactorController`: Mid-login OTP challenge workflow verifying TOTP tokens stored in `two_factor_secret`.
  - `WebApplicationFirewall`: Basic regex-based query/body payload inspection for SQL injection, XSS, and path traversal patterns; IP blocklist.
  - `SecurityHeaders`: Content-Security-Policy (CSP), X-Frame-Options (SAMEORIGIN), nosniff, Referrer-Policy.
- **Member Authentication Deficiencies**:
  - `config/auth.php` registers a `member` guard with `App\Models\Member`, but `Member` extended `Illuminate\Database\Eloquent\Model` instead of `Authenticatable`, preventing standard Laravel session auth.
  - Member API (`MemberApiController`) allows login using Member ID, Email, or Phone, producing a pseudo-token (`base64(id:guid:timestamp)`) with optional/unverified passwords.
  - Public portal relies on email OTPs stored in HTTP session (`renewal_otp`) for renewals, with no persistent authenticated Member session.

### 3.2 Member & Family Management
- **Models**: `Member`, `MemberChild`, `RenewalRequest`, `RenewalChild`, `StudentRequest`.
- **Fields Captured**: Full name, title, email, phone, DOB, house details, postal code, residing area, marital status, spouse name, spouse mobile, spouse DOB, emergency contact name/mobile, children details (name, sex, DOB), photos, bank account holder, payment proofs.
- **Data Encryption**:
  - Some fields are cast to `encrypted` in Eloquent models.
  - `HasSmartDecryption` trait dynamically decrypts string values prefixed with `eyJ` (Laravel encrypted payloads) on attribute access.
  - Raw member data cleanup route `/fix-ids` was used in past migrations to decrypt and store records in plaintext or ciphertext.

### 3.3 Financial & Accounting Records
- **Models**: `FinancialTransaction`, `PaymentHistory`.
- **Records**: Membership renewal fees, ticket sales, sponsor donations, and operational club expenses.
- **UK Statutory Obligation**: Financial records are subject to UK HMRC statutory retention requirements (minimum 6 years under Companies Act / Taxes Management Act 1970). They must **never** be indiscriminately erased under GDPR Right to Erasure requests.

### 3.4 Document & Photo Storage
- Member passport photos and family photos are stored under `storage/app/public/photos/`.
- Event banners and gallery images are under `storage/app/public/cms/` and `storage/app/public/uploads/`.
- **Storage Interceptor**: A custom `/img?p=` and universal route interceptor `{any_prefix}/{media_type}/{path}` serves public and private photos directly due to shared hosting symlink restrictions.
- **Privacy Vulnerability**: Photos and sensitive member documents lack signed, expiring URL enforcement. Anyone guessing or acquiring a photo filename could view member and children photos.

---

## 4. Third-Party Personal Data Flows

1. **Telegram Bot API (`api.telegram.org`)**:
   - Outbound notifications transmit administrative alerts, WAF attack warnings, terminal executions, and administrative account creations.
   - *Risk*: Must ensure member names, email addresses, or unmasked sensitive data are never transmitted in Telegram chat channels.
2. **OpenWA / WhatsApp Local Daemon (`whatsapp-server/`)**:
   - Dispatches OTP verification messages, member ID card notifications, and bulk community broadcast messages.
   - *Risk*: Mobile numbers and message payloads flow through a Node.js daemon. Strict logging sanitization and opt-out preferences required.
3. **SMTP Email Provider**:
   - Delivers OTP codes, membership confirmations, renewal notices, and PDF digital ID cards.
   - *Risk*: Plaintext email transport if TLS is misconfigured; email contents must avoid excessive personal data exposure.

---

## 5. Summary of Compliance Gaps

| Area | Current Application Status | UK GDPR Requirement | Gap Severity |
| :--- | :--- | :--- | :--- |
| **Data Subject Rights** | No portal for SAR, Rectification, Erasure, or Objection. | Articles 12–23 UK GDPR; 1 month response deadline. | **CRITICAL** |
| **Separation of Duties** | SuperAdmin has omnipotent access; developers have full DB access. | Principle of Least Privilege; Privacy-by-Design. | **HIGH** |
| **Audit Trails** | Limited `activity_logs` table (logs WAF & admin logins). | Accountability Principle (Art. 5(2)); auditable data access. | **HIGH** |
| **Data Retention** | Indefinite retention of inactive, expired, or deleted members. | Storage Limitation Principle (Art. 5(1)(e)). | **HIGH** |
| **Breach Management** | Ad-hoc error logging without structured 72-hour assessment. | Articles 33 & 34 UK GDPR (ICO notification). | **HIGH** |
| **Unauthenticated Routes** | Diagnostic routes (`/run-migrations`, `/fix-ids`, `/fix-storage`) open. | Secure engineering, OWASP Top 10 A01 (Broken Access Control). | **CRITICAL** |
