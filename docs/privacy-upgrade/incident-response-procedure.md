# Personal Data Breach & Security Incident Response Procedure
**Portsmouth Malayalee Community Club (PMCC-UK)**  
*Document Version: 1.0.0 | Compliance: UK GDPR Articles 33 & 34 | Review: Annual*

---

## 1. Statutory Obligations & The 72-Hour Rule

Under UK GDPR Article 33:
> *"In the case of a personal data breach, the controller shall without undue delay and, where feasible, not later than 72 hours after having become aware of it, notify the personal data breach to the Information Commissioner's Office (ICO), unless the personal data breach is unlikely to result in a risk to the rights and freedoms of natural persons."*

Under UK GDPR Article 34:
> *"When the personal data breach is likely to result in a high risk to the rights and freedoms of natural persons, the controller shall communicate the personal data breach to the data subject without undue delay."*

---

## 2. Five-Phase Incident Response Lifecycle

```
[ Phase 1: Identification & Detection ]
                  |
                  v
[ Phase 2: Containment & Risk Assessment ]
                  |
                  v
[ Phase 3: Statutory Notification Decision (ICO / Members) ]
                  |
                  v
[ Phase 4: Remediation & System Recovery ]
                  |
                  v
[ Phase 5: Post-Incident Review & Closure ]
```

---

## 3. Phase-by-Phase Instructions

### Phase 1: Identification & Registration
- Any committee member, volunteer, or technical contractor discovering a security anomaly or potential data leak must immediately register an incident in `/admin/privacy/incidents`.
- The system automatically assigns a unique identifier (`INC-YYYY-XXXXXX`), timestamps detection, and initiates the 72-hour regulatory countdown timer.

### Phase 2: Containment & Risk Assessment
- **Immediate Containment:**
  - Revoke compromised administrator sessions (`/admin/access-control`).
  - Rotate database credentials, application keys, and mail passwords if exposure is suspected.
  - Temporarily restrict external API traffic via Web Application Firewall (`/admin/security-audit`).
- **Severity Evaluation Matrix:**
  - **Low:** Minor internal misdirection without sensitive financial or identity details; contained immediately; low risk to individuals.
  - **Medium:** Unauthorized exposure of non-sensitive member contact info (names/emails) to a limited internal audience.
  - **High:** Exposure of sensitive credentials, financial records, or child dependent data; identity fraud or severe distress possible.
  - **Critical:** Full database compromise, mass unauthenticated credential theft, or ransomware encryption.

### Phase 3: Statutory Notification Decisions
- **ICO Notification Threshold:**
  - If the breach presents **any risk** to the rights and freedoms of individuals (e.g. potential for discrimination, identity theft, financial loss, damage to reputation), the ICO **must** be notified within 72 hours.
  - Notification portal: https://ico.org.uk/for-organisations/report-a-breach/ or phone 0303 123 1113.
  - The decision whether or not to notify, including detailed reasoning, must be permanently recorded in the Incident Register.
- **Data Subject Notification Threshold:**
  - If the risk is assessed as **high**, all affected members must be notified directly in clear and plain language without undue delay.

### Phase 4: Remediation & Recovery
- Patch the underlying technical vulnerability or software defect.
- Verify system integrity using automated security tests (`php vendor/phpunit/phpunit/phpunit tests/Feature/PrivacyGovernanceTest.php`).
- Restore corrupted or lost data from validated, encrypted backups.

### Phase 5: Closure & Accountability
- The Lead Investigator logs a complete timeline of containment and remediation actions.
- Post-incident lessons learned are documented and submitted to the Executive Committee.
- Incident records and associated audit entries are retained for a minimum of 3 years under the PMCC Data Retention Policy.
