# D-Tech CMS Second-Stage Technical Audit

Audit date: 2026-11-22

No source files, database tables, dependencies, or migrations were changed during this audit.

## Verified findings

| # | Severity | File | Lines | Finding | Confidence |
|---|----------|------|-------|---------|------------|
| 1 | 🔴 CRITICAL | CMS-backend/database/migrations/2026_09_28_164724_add_role_to_users_table.php | 12-33 | The role migration's `up()` method does nothing, and its `down()` method recreates columns using a misspelled `remeberToken()` call. | 10/10 |
| 2 | 🟠 HIGH | CMS-backend/app/Http/Controllers/AuthController.php | 13-25, 38-51 | Backend registration/login validation accepts passwords weaker than the frontend policy, and login/register do not revoke or limit existing tokens. | 9/10 |
| 3 | 🟠 HIGH | cms_db migration state | N/A | `cms_db` has no migration table. Existing core tables are not migration-tracked, Sanctum's table is absent, and `migrate:status` returns "Migration table not found." | 10/10 |
| 4 | 🟠 HIGH | CMS-frontend/src/pages/Login/Login.jsx | 61-68 | The bearer token is stored in `localStorage`, and the dashboard is navigated to immediately after login without validating or restoring authentication state. | 9/10 |
| 5 | 🟡 MEDIUM | CMS-frontend/src/pages/Register/Register.jsx | 81-92 | Registration UI validation is complete, but the register API call is not implemented. | 10/10 |
| 6 | 🟡 MEDIUM | CMS-frontend/src/App.jsx | 11-18 | The dashboard route has no frontend route guard. | 10/10 |
| 7 | 🟡 MEDIUM | CMS-backend/database/migrations/2026_09_29_144452_create_assignments_table.php; CMS-backend/database/migrations/2026_09_29_144508_create_submissions_table.php | 14-16 | Repository migrations would create only `id` and timestamps, while `cms_db` already contains assignment/submission business columns. | 10/10 |
| 8 | 🟡 MEDIUM | cms_db | N/A | The existing assignments, submissions, and contents relationships have no foreign-key constraints. | 10/10 |
| 9 | 🟡 MEDIUM | CMS-backend/app/Models/User.php | 21-25 | `role` is mass assignable even though no role assignment API exists. Future request-to-model code could permit privilege escalation. | 7/10 |
| 10 | ⚪ LOW | CMS-frontend/src/pages/Register/Register.jsx | 2 | An unused `React` import fails ESLint. | 10/10 |

## Actual database connection status

- Laravel default connection: `mysql`
- Driver: `mysql`
- Active database: `cms_db`
- Read-only connection test: successful
- Migration table in `cms_db`: absent
- `php artisan migrate:status`: "Migration table not found."

The earlier SQLite report was based on the SQLite fallback/example configuration and the presence of `database/database.sqlite`. The actual `.env` configuration uses MySQL and the live Laravel connection resolves to `cms_db`.

## Existing `cms_db` schema

The database contains:

- `users`: `id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`
- `assignments`: `id`, `title`, `description`, `teacher_id`, `due_date`, `created_at`, `updated_at`
- `submissions`: `id`, `assignment_id`, `student_id`, `file_path`, `grade`, `submitted_at`
- `contents`: `id`, `title`, `body`, `category`, `author_id`, `status`, `created_at`, `updated_at`

The schema contains no foreign-key constraints.

The following Laravel framework tables are absent from `cms_db`:

- `migrations`
- `personal_access_tokens`
- `password_reset_tokens`
- `sessions`
- `cache` and `cache_locks`
- `jobs`, `job_batches`, and `failed_jobs`

## Migration conflict report

| Migration | Conflict |
|-----------|----------|
| Users migration | Existing `cms_db.users` conflicts with the migration's `create users` operation because no migration history exists. Existing columns differ from the migration in column names/types, notably role and verification/remember fields. |
| Role migration | `up()` has no role operation. The migration also has an invalid typo in `down()`. |
| Sanctum migration | The table is absent from `cms_db` and must be reconciled before bearer-token login can work reliably. |
| Assignments migration | Existing assignment table is richer than this migration's only `id` and timestamps. |
| Submissions migration | Existing submission table is richer than this migration's only `id` and timestamps. |
| Tasks migration | The migration creates a generic tasks table, while no tasks table exists in `cms_db`; an unrelated `dashboard.tasks` table exists on the MySQL server. |
| Cache migration | Cache tables are absent from `cms_db` and may be needed depending on configured cache/session drivers. |
| Jobs migration | Job tables are absent from `cms_db` and may be needed for configured queue jobs. |

Safe reconciliation plan:

1. Create a verified database backup and restore test.
2. Export and retain the existing `cms_db` schema and migration metadata.
3. Correct the role migration and down migrations.
4. Add a migration for `contents`.
5. Reconcile existing tables through explicit baseline migrations or one-time approved schema operations.
6. Create a migration history table only after deciding which existing migrations should be marked as already applied.
7. Create missing personal-access-token, password-reset, sessions, cache, and queue tables.
8. Add foreign keys with suitable cascade behavior after identifying the required ownership and deletion rules.
9. Run migration status and the test suite only after approval.

Do not run migrations until the reconciliation approach is explicitly approved.

## Authentication security

Verified:

- Registration and login validate basic required fields.
- Passwords are hashed with Laravel's Hash implementation.
- Sanctum `HasApiTokens` is enabled on the user model.
- Login and registration create Sanctum bearer tokens.
- Logout deletes the current access token.
- `/api/user` and `/api/logout` use `auth:sanctum`.

Risks:

- Registration requires only eight characters, while the frontend imposes stronger complexity.
- Sanctum token creation is blocked or unreliable until `personal_access_tokens` exists.
- Token expiration is not configured (`null`).
- No custom role middleware or role authorization policy is present.
- The login page stores the bearer token in `localStorage`.
- The dashboard route has no authentication guard.
- Registration is not connected to the backend.
- Logout UI removes only the local token and does not call `/api/logout`.

## Composer advisories

| Package | Advisory | Installed | Vulnerable range | Fixed version for installed branch | Recommendation |
|---------|----------|-----------|------------------|-----------------------------------|----------------|
| laravel/framework | PKSA-yxbx-3c9s-cjt9 / CVE-2026-128083 | v12.69.2 | >=13.0.0,<13.40.0 or <12.71.0 | >=12.71.0 | Upgrade to Laravel 12.71.0 or a later compatible 12.x release. |
| symfony/http-foundation | PKSA-nxjs-s6hn-vb6m | v7.4.19 | 7.4.x before 7.4.23 | >=7.4.23 | Upgrade the Symfony 7.4 component set to 7.4.23 or later. |
| symfony/http-foundation | PKSA-24ph-xkrh-y79j | v7.4.19 | 7.4.x before 7.4.23 | >=7.4.23 | Upgrade the Symfony 7.4 component set to 7.4.23 or later. |
| symfony/http-foundation | PKSA-pr6q-2w5s-kzzf | v7.4.19 | 7.4.x before 7.4.23 | >=7.4.23 | Upgrade the Symfony 7.4 component set to 7.4.23 or later. |
| symfony/http-foundation | PKSA-5mx8-mwrp-5693 / NO CVE | v7.4.19 | 7.4.x before 7.4.23 | >=7.4.23 | Upgrade the Symfony 7.4 component set to 7.4.23 or later. |
| symfony/http-kernel | PKSA-3gxv-7xhq-mgmz / CVE-2026-123339 | v7.4.19 | 7.4.x before 7.4.23 | >=7.4.23 | Upgrade symfony/http-kernel to 7.4.23 or later. |
| symfony/mime | PKSA-wtxr-p26d-nn42 / CVE-2026-45070 | v7.4.19 | 7.4.x before 7.4.22 | >=7.4.22 | Upgrade symfony/mime to 7.4.22 or later. |
| symfony/routing | PKSA-y8c5-1b7f-r1gj / CVE-2026-118516 | v7.4.18 | 7.4.x before 7.4.23 | >=7.4.23 | Upgrade symfony/routing to 7.4.23 or later. |
| symfony/yaml | PKSA-nktx-f1n1-b4yd / CVE-2026-123792 | v7.4.18 | 7.4.x before 7.4.23 | >=7.4.23 | Upgrade symfony/yaml to 7.4.23 or later. |

The installed Symfony components are not currently all at the advisory fixed versions. Upgrade is not performed.

## Git security

- `.env`: not tracked and ignored by `CMS-backend/.gitignore`.
- SQLite database: not tracked and ignored by `CMS-backend/database/.gitignore`.
- `cms_db_backup_2026-11-16.sql`: untracked and not covered by the backend ignore rule.
- No committed secret-pattern match was found in tracked files using application-key, database-password, AWS, GitHub-token, and private-key pattern checks.
- This broad-pattern check does not prove that every possible secret or personal-data value is absent.
- The SQL backup should be reviewed and added to an appropriate ignore rule if it is not required as a repository artifact.

## Unverified findings

- Whether the existing MySQL rows are valid and contain personal or test data.
- Whether the SQL backup is complete or restorable.
- Whether the production server uses any additional web-server proxy configuration or Sanctum stateful domains.
- Whether the Symfony advisories are reachable from the project deployment surface.
- Whether any secret values exist in commit history, because no full-history secret scan was performed.

## Recommended implementation order

1. Fix and baseline the MySQL migration state with a backup.
2. Restore or create the missing Sanctum table safely.
3. Complete secure registration/login integration.
4. Implement frontend route guards and role-aware navigation.
5. Add backend role middleware, policies, ownership checks, and API resources.
6. Implement assignment, submission, content, and user management modules.
7. Replace static dashboard data with role-specific API endpoints.
8. Upgrade Composer dependencies after compatibility testing.
9. Add focused authentication, authorization, migration, upload, and API tests.
