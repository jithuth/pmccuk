# Privacy Request Management Workflow (UK GDPR Articles 12-22)
**PMCC-UK Laravel Application Platform**  
*Document Version: 1.0.0 | Operational Privacy Guidance*

---

## 1. Overview

This document specifies the standard operating procedure (SOP) and automated workflow for receiving, verifying, investigating, approving, and fulfilling individual privacy rights requests submitted by PMCC members.

Supported statutory requests:
1. **Subject Access Request (SAR)** (Article 15)
2. **Right to Rectification** (Article 16)
3. **Right to Erasure / "Right to be Forgotten"** (Article 17)
4. **Right to Restriction of Processing** (Article 18)
5. **Right to Data Portability** (Article 20)
6. **Right to Object to Processing** (Article 21)
7. **Consent Withdrawal** (Article 7(3))
8. **General Privacy Complaints & Inquiries**

---

## 2. Request Lifecycle & State Transitions

```
[Member Submits Request]
         |
         v
   ( Submitted )
         |
         v
[Identity Review] -------> [Additional Info Required] (Clock Paused)
         |                              |
         | (Verified)                   | (Proof Received)
         v                              v
  ( Data Discovery ) <------------------+
         |
         v
   ( Legal Review )
         |
    +----+--------------------------------+
    |                                     |
    v (Approved)                          v (Exemption / Refused)
( In Fulfilment )                   ( Refused )
    |                               [Formal Notice with ICO Rights]
    v
[Export / Rectify / Anonymize]
    |
    v
( Completed )
[Secure Download Available for 7 Days]
```

---

## 3. Statutory Deadline Calculation Rules

Under UK GDPR Article 12(3) and Information Commissioner's Office (ICO) guidelines:
1. **Initial Period:** The organization must respond without undue delay and at the latest within **one calendar month** of receiving the request.
2. **Day 1 Rule:** The response clock begins on the day after the request is received.
3. **Corresponding Date Rule:** If a request is received on 31 August, the statutory deadline is 30 September. If received on 31 January, the statutory deadline is the last day of February (28th or 29th in a leap year).
4. **Non-Working Days Rollover:** If the calculated deadline date falls on a weekend or a UK statutory public bank holiday, the deadline automatically rolls forward to the **next immediate business day**.
5. **Complex Request Extension:** Where requests are complex or numerous, the Privacy Officer may extend the deadline by up to **two additional calendar months** (maximum three months total). An extension notice specifying reasons must be dispatched to the requester within the first month.

---

## 4. Operational Requirements by Request Type

### 4.1 Subject Access Request (SAR)
- **Scope:** Complete personal information profile, linked family records, registration submission dates, event bookings, and membership renewal records.
- **Third-Party Redaction:** Records containing personal data of unrelated third parties (other attendees, non-consenting co-members) are excluded or redacted.
- **Delivery:** Structured JSON and machine-readable CSV files, protected by a cryptographically random 64-character token expiring in 7 days with single-recipient verification.

### 4.2 Right to Rectification
- Member submits existing value and proposed replacement with justification.
- Privacy Officer reviews changes against official documents (e.g. deed poll, updated passport).
- Once approved, the record is updated in the database, logged to the security audit trail, and the member is notified.

### 4.3 Right to Erasure ("Right to be Forgotten")
- **CRITICAL RULE: Data erasure must NEVER automatically terminate an active membership.**
- **Legal Hold Precedence:** Financial records and accounting transaction logs **cannot** be deleted under UK law (HMRC requirement to maintain records for 6 years from the end of the last company financial year).
- **Discretionary Data Disposal:** Discretionary records (photos, promotional consents, non-essential contact details) are anonymized or permanently purged.

---

## 5. Neutral Dispute Resolution Workflow
When a member registers a formal grievance or complaint regarding data handling:
1. The case is registered under an objective reference (`SAR-YYYY-XXXXXX` or `CMP-YYYY-XXXXXX`).
2. No subjective allegations or personal disputes are recorded in database tables; only objective facts, requested actions, and legal determinations are logged.
3. Independent review by the designated Privacy Officer and legal counsel is documented before any formal response is issued.
4. Every refusal or partial fulfilment notice includes explicit statutory guidance on the member's right to complain to the **Information Commissioner's Office (ICO)** (Wycliffe House, Water Lane, Wilmslow, Cheshire SK9 5AF | https://ico.org.uk).
