# Data Retention Policy & Schedule (UK GDPR Article 5(1)(e))
**Portsmouth Malayalee Community Club (PMCC-UK)**  
*Document Version: 1.0.0 | Review Frequency: Annual | Policy Owner: Data Protection Officer*

---

## 1. Statutory Context & Principles

Under Principle (e) of the UK General Data Protection Regulation (Storage Limitation):
> *"Personal data shall be kept in a form which permits identification of data subjects for no longer than is necessary for the purposes for which the personal data are processed."*

This document defines the retention periods, statutory triggers, disposal actions, and legal holds for each category of personal data processed by PMCC-UK.

---

## 2. Retention Schedule

| Category Code | Data Category | Processing Purpose | Lawful Basis | Retention Period | Statutory Trigger | Disposal Method | Legal Hold / Exemption |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `MEM-01` | Active Membership Records | Club administration, voting rights, communications | Contract (Art 6(1)(b)) | Life of Membership + 2 Years | Date of resignation, non-renewal, or lapse | Anonymization (`privacy_anonymized_at`) | Excludes financial receipts |
| `MEM-02` | Dependents & Family Records | Family event ticketing, child safeguarding | Legitimate Interest (Art 6(1)(f)) | Life of Family Membership + 1 Year | Departure or child turning 18 | Secure Purge / Anonymization | Safeguarding incident logs retained 25 years |
| `FIN-01` | Financial Transactions & Dues | Accounting, statutory tax reporting, auditing | Legal Obligation (Art 6(1)(c)) | **6 Years + Current Financial Year** | Date of financial year end | Irreversible Archival / Anonymization | **HMRC Taxes Management Act 1970** |
| `EVT-01` | Event Bookings & Attendance | Gate control, ticketing, capacity management | Contract / Legitimate Interest | 1 Year from Event Completion | Date of event conclusion | Anonymization of names, phone, email | Health & Safety incidents retained 3 years |
| `PRV-01` | Privacy Requests & SAR Files | Accountability, proving compliance to ICO | Legal Obligation (Art 6(1)(c)) | 3 Years from Request Closure | Date of final closure or response | Secure Deletion | Ongoing ICO complaints or litigation |
| `AUD-01` | Security & Access Audit Logs | Forensic audit, intrusion detection, tamper evidence | Legitimate Interest (Art 6(1)(f)) | 2 Years | Timestamp of event generation | Cold Storage Export / Purge | Active cyber incident investigation |
| `MKT-01` | Marketing & WhatsApp Consents | Cultural newsletters, optional notifications | Consent (Art 6(1)(a)) | Valid until Consent Withdrawn | Date consent withdrawn or unverified | Immediate Deactivation / Revocation | Proof of prior consent retained 2 years |
| `DOC-01` | Uploaded Identity / ID Proofs | Membership verification | Legitimate Interest | 90 Days post-approval | Date of membership approval | Permanent Deletion from private storage | Rejected applications held 30 days |

---

## 3. Legal Hold Procedures

A **Legal Hold** supersedes all routine disposal actions.
1. When litigation, a formal member dispute, or an ICO investigation is reasonably anticipated, the Privacy Officer or President issues an explicit Legal Hold order on affected records.
2. The `privacy_hold` flag is set on the member or incident record, preventing automatic or manual deletion.
3. No system administrator or automated retention cron job may override a Legal Hold until formal written release is issued by legal counsel.

---

## 4. Anonymization Standards

Where disposal action is defined as `anonymize`:
1. Identifiers (`full_name`, `email`, `mobile_number`, `house_details`, `photo`) are overwritten with irreversible synthetic tokens (`ANON-XXXXX`).
2. Postcodes are truncated to outward code only (`PO1 ***`).
3. Relationships to financial ledgers are preserved in aggregate so accounting totals match without identifying the individual.
