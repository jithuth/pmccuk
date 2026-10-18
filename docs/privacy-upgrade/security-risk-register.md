# Security Risk Register & Threat Assessment
**PMCC-UK Laravel Application – UK GDPR, Privacy Governance & Security Upgrade**

---

## 1. Risk Evaluation Methodology

Risks are categorized according to the **OWASP Top 10 (2021)**, **NIST Cybersecurity Framework**, and **UK GDPR / Data Protection Act 2018** enforcement guidelines.
Severity is scored on a standard Impact vs. Likelihood matrix:
- **Critical (Red)**: Imminent threat of regulatory penalty, personal data breach, or unauthorized system compromise.
- **High (Orange)**: Major governance or architectural vulnerability violating core security or privacy principles.
- **Medium (Yellow)**: Deficiency requiring technical controls to prevent privilege escalation or data leakage.
- **Low (Green)**: Informational or defense-in-depth improvement.

---

## 2. Active Risk Register

| Risk ID | Vulnerability / Threat Description | OWASP / GDPR Reference | Initial Severity | Technical Remediation Control | Post-Mitigation Severity |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **SEC-01** | **Unauthenticated Maintenance & Migration Routes**<br>Routes `/run-migrations`, `/fix-ids`, `/fix-storage`, `/clear-everything` are exposed to public unauthenticated requests. | OWASP A01: Broken Access Control | **CRITICAL** | Remove public endpoints or protect them behind strict `auth:admin` + `admin.role:superadmin` middleware with environment protection (`APP_ENV !== 'production'`). | **LOW** |
| **SEC-02** | **Insecure Mobile API Authentication & Token Forgery**<br>`MemberApiController::login` allows authentication via email/mobile without validating password; tokens are predictable base64 (`id:guid:time`). | OWASP A07: Identification and Authentication Failures | **CRITICAL** | Implement cryptographically signed Sanctum personal access tokens or HMAC-SHA256 bearer tokens with password/OTP verification and rate limiting. | **LOW** |
| **SEC-03** | **Unrestricted Personal Data Bulk Exports**<br>Admins can download unredacted attendee lists and financial exports without approval, justification, or audit logging. | UK GDPR Art. 5(1)(f) (Integrity and Confidentiality); Art. 32 | **HIGH** | Implement `members.export` permission gate, mandatory access justification prompt, independent Privacy Officer approval for bulk exports, and audit logging. | **LOW** |
| **SEC-04** | **Excessive Developer & SuperAdmin Privileges**<br>Developers and administrators automatically possess unrestricted visibility into sensitive family, medical, and financial data. | UK GDPR Art. 25 (Privacy by Design); Least Privilege | **HIGH** | Implement fine-grained RBAC. Restrict developer accounts from viewing confidential PII. Introduce time-limited Break-Glass emergency access workflow with audit trail. | **LOW** |
| **SEC-05** | **Absence of Centralized UK GDPR Rights Management**<br>No auditable mechanism to receive, track, and fulfill Subject Access Requests, Erasure, or Rectifications within the 1-month statutory window. | UK GDPR Articles 12–23; ICO Enforcement | **HIGH** | Deploy centralized Privacy Request Management System with statutory deadline calculator, reviewer assignment, status tracking, and secure fulfillment downloads. | **LOW** |
| **SEC-06** | **Indefinite Data Retention & Storage Limitation Exposure**<br>Historical member data, expired renewals, and uploaded identity documents remain stored indefinitely. | UK GDPR Art. 5(1)(e) (Storage Limitation) | **HIGH** | Implement automated Data Retention Policies per data category, distinguishing between operational records (erasable) and financial records (statutory 6-year retention). | **LOW** |
| **SEC-07** | **Insecure Direct Object Reference (IDOR) on Digital ID Cards**<br>Route `/id-card/view/{guid}` serves member ID cards based solely on a GUID without authenticated authorization or expiring signed URLs. | OWASP A01: Broken Access Control | **HIGH** | Require member authentication or temporary signed cryptographic URLs with short expiry for digital ID viewing. | **LOW** |
| **SEC-08** | **Potential PII Leakage via External Telegram / WhatsApp Alerts**<br>Administrative notifications sent to Telegram chat channels or WhatsApp could expose personal data (member names, unmasked emails, IPs). | UK GDPR Art. 32; Data Minimization | **MEDIUM** | Enforce data minimization on all outbound notifications. Strip or mask PII in message bodies (e.g. `J*** D***`, `PMCC-****12`). | **LOW** |
| **SEC-09** | **Lack of Multi-Party Authorization on Erasure Operations**<br>Single administrator could delete or modify member records without secondary confirmation or recorded legal justification. | UK GDPR Art. 24 (Accountability); Separation of Duties | **MEDIUM** | Enforce independent approval workflow for high-risk operations (erasure, permanent deletion, mass export). | **LOW** |
| **SEC-10** | **Unstructured Security Incident Response**<br>No formal incident register or automated workflow to evaluate the 72-hour ICO personal data breach notification threshold. | UK GDPR Articles 33 & 34 | **MEDIUM** | Implement structured Security Incident Management module with severity ranking, ICO notification assessment, and chronological containment timeline. | **LOW** |
