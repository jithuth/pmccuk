# Production Deployment & Migration Runbook: Privacy Upgrade
**PMCC-UK Laravel Application Platform**  
*Document Version: 1.0.0 | Release Type: Additive Schema & Security Hardening*

---

## 1. Pre-Deployment Verification

> [!CAUTION]
> Never run `php artisan migrate:fresh`, `db:wipe`, or destructive table truncation commands on production servers. All migrations are purely additive and preserve all existing rows, tables, and foreign keys.

- [ ] **Full Database Backup:** Take a complete, cold MySQL dump before touching production:
  ```bash
  mysqldump -u u601819832_pmcc_v2 -p u601819832_pmcc_v2 --single-transaction --quick > backup_pre_privacy_upgrade_$(date +%Y%m%d_%H%M%S).sql
  ```
- [ ] **Backup Verification:** Verify backup file size and integrity (`ls -lh backup_*.sql`).
- [ ] **Filesystem Backup:** Snapshot `storage/app/` and `.env`.
- [ ] **Staging Sign-Off:** Confirm all 13 automated feature tests pass in the staging environment.

---

## 2. Staged Deployment Steps

### Step 1: Maintenance Mode (Optional, recommended for zero data drift)
```bash
php artisan down --secret="pmcc-privacy-maintenance-2026"
```

### Step 2: Code Pull
```bash
git checkout main
git pull origin main
```

### Step 3: Composer Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### Step 4: Additive Database Migrations
Execute pending migrations without dropping or altering existing legacy tables:
```bash
php artisan migrate --force
```
*Migrations executed:*
- `2026_10_19_000001_create_privacy_governance_rbac_tables.php`
- `2026_10_19_000002_add_auth_and_privacy_columns_to_members_table.php`
- `2026_10_19_000003_create_privacy_request_management_tables.php`
- `2026_10_19_000004_create_security_audit_and_incident_tables.php`
- `2026_10_19_000005_create_data_retention_and_consent_tables.php`

### Step 5: Seed Privacy Roles, Permissions & Retention Policies
```bash
php artisan db:seed --class=Database\\Seeders\\PrivacyGovernanceSeeder --force
```
*Note:* The seeder uses `updateOrCreate` and checks existing rows, preventing duplicate records.

### Step 6: Storage & Directory Permissions
Ensure private export directory exists with restrictive permissions:
```bash
mkdir -p storage/app/privacy_exports
chmod -R 775 storage bootstrap/cache
```

### Step 7: Clear & Optimize Caches
```bash
php artisan view:clear
php artisan route:clear
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 8: Resume Normal Traffic
```bash
php artisan up
```

---

## 3. Post-Deployment Verification

1. [ ] Log in to Admin panel via `/tomjsailor` with administrator credentials.
2. [ ] Verify "PRIVACY & GOVERNANCE" section appears in the left sidebar.
3. [ ] Visit `/admin/privacy/dashboard` to verify KPI cards load without errors.
4. [ ] Attempt visiting `/clear-everything` as an unauthenticated user; verify it redirects to login (public lockdown active).
5. [ ] Navigate to `/member/login` and test logging into the Member Privacy Center.
6. [ ] Submit a test Subject Access Request in the Member Privacy Center and confirm it appears in the Officer Dashboard (`/admin/privacy/requests`).
7. [ ] Verify security audit log record was created for the login and submission in `/admin/privacy/audit`.

---

## 4. Rollback Plan
If an unexpected critical regression occurs during deployment:
1. Revert to previous Git commit:
   ```bash
   git checkout HEAD~1
   ```
2. Restore database from pre-deployment snapshot if necessary:
   ```bash
   mysql -u u601819832_pmcc_v2 -p u601819832_pmcc_v2 < backup_pre_privacy_upgrade_*.sql
   ```
3. Re-cache application configurations:
   ```bash
   php artisan config:clear && php artisan cache:clear
   ```
