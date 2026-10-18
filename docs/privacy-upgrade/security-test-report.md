# Security & Automated Compliance Test Report
**Portsmouth Malayalee Community Club (PMCC-UK)**  
*Document Version: 1.0.0 | Environment: PHP 8.2.12 / Laravel 12.56.0 / PHPUnit 11.5.55*

---

## 1. Executive Summary

A comprehensive automated test suite (`tests/Feature/PrivacyGovernanceTest.php`) was implemented to validate all technical controls required by UK GDPR, OWASP Top 10 web application security principles, and the PMCC Privacy Upgrade specification.

**Overall Test Suite Result: 13 Tests Passed | 44 Assertions | 0 Failures | 0 Errors (100% Pass Rate)**

---

## 2. Test Execution Matrix

| Test Method | Category | Requirement / Risk Tested | Result | Assertions |
| :--- | :--- | :--- | :---: | :---: |
| `test_unauthenticated_user_cannot_access_member_privacy_center` | Access Control | Unauthenticated visitors attempting to view `/member/privacy` are redirected to member login | **PASS** | 1 |
| `test_authenticated_member_can_view_own_privacy_center` | Data Access | Authenticated member can view their authorized profile and family data | **PASS** | 2 |
| `test_member_cannot_download_unauthorized_or_forged_document` | IDOR / Cryptography | Member Alice attempting to download Bob's SAR export token receives HTTP 403 Forbidden | **PASS** | 1 |
| `test_diagnostic_endpoints_reject_unauthenticated_requests` | Security Hardening | Critical maintenance routes (`/clear-everything`, `/run-migrations`, `/fix-storage`) reject public access | **PASS** | 3 |
| `test_diagnostic_endpoints_reject_regular_admin` | Privilege Escalation | Regular administrator without `superadmin` role cannot trigger maintenance scripts (HTTP 403) | **PASS** | 1 |
| `test_developer_cannot_access_member_records_without_break_glass_grant` | Least Privilege | Developer account cannot access member personal records or privacy dashboards (HTTP 403) | **PASS** | 1 |
| `test_admin_cannot_approve_own_break_glass_access` | Anti-Self-Approval | Administrator cannot approve their own break-glass elevation grant (HTTP 403) | **PASS** | 1 |
| `test_statutory_deadline_calculator_rules` | UK GDPR Article 12 | Accurate 1-calendar-month calculation, leap year / month-end clipping, and weekend-to-Monday rollover | **PASS** | 2 |
| `test_data_retention_and_erasure_protects_legal_financial_records` | Data Retention | Selective anonymization of personal data protects statutory HMRC accounting ledgers from deletion | **PASS** | 4 |
| `test_member_api_login_and_token_signature_verification` | API Security | API requires password verification; HMAC SHA-256 tokens reject forged signatures | **PASS** | 5 |
| `test_member_web_portal_login_flow` | Authentication | Member web portal enforces Bcrypt password hashing and rejects invalid credentials with session error | **PASS** | 3 |
| `test_member_can_submit_subject_access_request` | Subject Rights | Member SAR submission creates pending record with auto-computed statutory deadline | **PASS** | 4 |
| `test_member_can_update_consent_preferences` | Consent Governance | Member can independently grant and revoke granular communication and processing preferences | **PASS** | 4 |

---

## 3. Cryptographic Verification Summary

1. **API Bearer Tokens:**
   - Tokens now employ HMAC SHA-256 signatures tying `member_id`, `guid`, and `timestamp` to `config('app.key')`.
   - Any modification to the payload invalidates the signature, returning `401 Unauthorized`.
   - Max lifetime of 60 days prevents indefinite replay of stale credentials.

2. **SAR Document Download Tokens:**
   - Generated with 64 cryptographically random bytes (`Str::random(64)`).
   - Time-limited validity (7 days max).
   - Server-side IDOR policy verifies ownership against `privacy_requests.member_id` before streaming bytes.

3. **Database Casts:**
   - Sensitive financial transaction descriptions and references retain existing Laravel encryption casts (`'encrypted'`).
