# Executive Committee Handover & Governance Manual
**Portsmouth Malayalee Community Club (PMCC-UK)**  
*Document Version: 1.0.0 | Target Audience: President, Secretary, Treasurer, Privacy Officer, Executive Committee*

---

## 1. Introduction & Governance Context

This handover manual provides the Executive Committee and newly designated Privacy Officers with a practical, non-technical understanding of the privacy and security controls implemented in the PMCC Laravel Application.

### Critical Legal Notice
> [!IMPORTANT]
> **Software controls alone do NOT guarantee legal compliance with UK GDPR.**  
> Technical mechanisms (such as access locks, request queues, and audit logs) assist the organization in meeting its statutory duties, but organizational policies, human decision-making, identity verification rigor, and timely handling of member communications are equally required by law.

---

## 2. Key Administrative Dashboards

### 2.1 The Privacy & Governance Hub (`/admin/privacy/dashboard`)
Accessible by the Privacy Officer, President, and Super Administrator:
- **Statutory Deadlines Watch:** Alerts the committee to requests approaching their 30-day statutory response deadline or overdue requests.
- **Pending Identity Verifications:** Flags requests where official photo ID or session verification has not yet been confirmed.
- **72-Hour Personal Data Breach Monitor:** Tracks active security incidents against the statutory Information Commissioner's Office (ICO) notification window.
- **Retention Actions:** Summarizes expired data categories eligible for safe disposal or anonymization.

### 2.2 Privacy Request Case Management (`/admin/privacy/requests`)
- View full chronological history of member requests.
- Verify identity with one click (or request supplementary identity documents).
- Assign an authorized investigator or legal reviewer.
- Extend complex requests by up to 2 additional calendar months with mandatory written justification.
- Record dual approvals for high-risk operations (e.g. data export or selective erasure).
- Automatically compile and package Subject Access Request (SAR) datasets into encrypted, expiring ZIP/JSON bundles.
- Record formal refusal with statutory exemptions and ICO complaint notices.

### 2.3 Privileged Break-Glass Elevation (`/admin/privacy/access`)
- When technical contractors or software developers need to troubleshoot a production issue, they **cannot** see member records by default.
- They must submit an emergency request stating their operational justification and requested duration (1 to 24 hours).
- An Executive Officer (President, Secretary, or Superadmin) reviews and grants access.
- **The system prevents anyone from granting elevation to themselves.**
- All elevated actions are logged to the tamper-evident audit trail, and access expires automatically.

---

## 3. Organizational Actions Required by the Committee

To maintain operational compliance, the Executive Committee must complete the following governance actions:

1. **Formally Appoint a Data Protection / Privacy Officer:**
   - Designate a trusted committee member with appropriate training and independence to act as the primary contact for member privacy inquiries and the ICO.
2. **Review & Approve the Data Retention Schedule:**
   - Formally review `docs/privacy-upgrade/data-retention-policy-template.md` and ratify retention periods (particularly preserving the 6-year HMRC financial obligation).
3. **Establish Independent Review for Complaints:**
   - Ensure privacy grievances or complaints are treated neutrally under standard workflows rather than personal disputes.
4. **Sign Off Third-Party Processor Agreements:**
   - Confirm that third-party service providers (Hostinger for web hosting, Telegram for bot alerts, Twilio/WhatsApp for messaging) have written Data Processing Agreements (DPAs) or Standard Contractual Clauses in place.
5. **Periodic Access Review:**
   - Review administrative user accounts quarterly in `/admin/access-control` to deactivate former committee members and rotate credentials.

---

## 4. Summary of Implemented Upgrades

- **Zero Data Loss:** All legacy member records, event bookings, transactions, and photos are preserved with zero destructive alterations.
- **Member Empowerment:** Members now have a dedicated, mobile-friendly Privacy Center (`/member/privacy`) to inspect their authorized data, update consent preferences, request corrections, and download complete SAR disclosures.
- **Hardened Security:** Unauthenticated diagnostic maintenance routes have been locked down, API tokens cryptographically signed, and tamper-evident audit logging integrated across the entire platform.
- **Automated Testing:** 13 comprehensive feature tests verify least-privilege security, IDOR protection, anti-self-approval enforcement, and deadline calculations with a 100% pass rate.
