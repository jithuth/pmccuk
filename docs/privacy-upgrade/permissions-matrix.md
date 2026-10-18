# Role & Permissions Matrix: UK GDPR Access Control
**PMCC-UK Laravel Application Platform**  
*Document Version: 1.0.0 | Security Classification: Official-Sensitive*

---

## 1. Principles of Least Privilege & Default-Deny

In accordance with UK GDPR Article 25 (Data Protection by Design and by Default) and Article 32 (Security of Processing), user access in the PMCC application operates under a strict **default-deny** paradigm. 

Key principles:
1. **Technical Administrator vs. Data Governance Separation:** Having developer or server administrative privileges does **not** grant authorization to inspect or export member personal data.
2. **Anti-Self-Approval:** Privileged access elevations and high-risk bulk data exports require independent multi-party authorization.
3. **Departmental Compartmentalization:** Financial records and general personal data are segregated; Treasurers have access to financial ledgers but cannot export unrelated family or student personal records.

---

## 2. Granular Permissions Definition

| Permission Slug | Description | Risk Level |
| :--- | :--- | :--- |
| `members.view.own` | Member can view their own personal profile and authorized dependents | Low |
| `members.view.assigned` | Operational staff can view attendee records for check-in purposes | Medium |
| `members.view.sensitive` | Authorized officer can view sensitive fields (e.g. emergency contacts, DOB) | High |
| `members.update` | Authorized administrator can update member status and profile fields | High |
| `members.export` | Authorized officer can trigger filtered personal data exports | Critical |
| `financials.view` | Treasurer or auditor can view financial transactions and accounting ledgers | Medium |
| `financials.export` | Treasurer or auditor can export financial reconciliations | High |
| `privacy.requests.create` | Member or officer on behalf can register a formal privacy request | Low |
| `privacy.requests.review` | Privacy Officer can review identity verification and request merits | Medium |
| `privacy.requests.approve` | Privacy Officer can record formal decisions on requests | High |
| `privacy.requests.fulfil` | Privacy Officer can package SAR exports or execute rectifications | High |
| `privacy.requests.close` | Privacy Officer can close completed privacy requests | Low |
| `audit.logs.view` | Auditor or Privacy Officer can review security audit logs | High |
| `security.sessions.manage`| Administrator can terminate active sessions and view 2FA status | High |
| `security.access.grant` | Executive supervisor can approve break-glass emergency elevation | Critical |

---

## 3. Organizational Roles Matrix

| Permission | Super Admin | President | Secretary | Treasurer | Membership Admin | Privacy Officer | Committee Member | Developer | Member |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| `members.view.own` | Yes | Yes | Yes | Yes | Yes | Yes | Yes | Yes | **Yes** |
| `members.view.assigned`| Yes | Yes | Yes | No | Yes | Yes | Yes | No* | No |
| `members.view.sensitive`| Yes | Yes | Yes | No | Yes | Yes | No | No* | No |
| `members.update` | Yes | Yes | Yes | No | Yes | No | No | No* | No |
| `members.export` | Yes** | No | No | No | No | Yes** | No | No* | No |
| `financials.view` | Yes | Yes | No | **Yes** | No | No | No | No* | No |
| `financials.export` | Yes | Yes | No | **Yes** | No | No | No | No* | No |
| `privacy.requests.create`| Yes | Yes | Yes | No | Yes | **Yes** | No | No | **Yes** |
| `privacy.requests.review`| No | Yes | No | No | No | **Yes** | No | No* | No |
| `privacy.requests.approve`| No | Yes | No | No | No | **Yes** | No | No* | No |
| `privacy.requests.fulfil` | No | No | No | No | No | **Yes** | No | No* | No |
| `privacy.requests.close` | No | Yes | No | No | No | **Yes** | No | No* | No |
| `audit.logs.view` | **Yes** | Yes | No | No | No | **Yes** | No | No* | No |
| `security.sessions.manage`| **Yes** | No | No | No | No | **Yes** | No | No | No |
| `security.access.grant` | **Yes** | **Yes** | No | No | No | No | No | No | No |

*\* Note on Developer Role:* Developers have zero PII permissions by default. Any temporary inspection of production data requires an active, time-limited Break-Glass grant approved by the President or Super Administrator.  
*\*\* Note on Exports:* Member bulk exports require dual approval and documented justification logged to the security audit trail.
