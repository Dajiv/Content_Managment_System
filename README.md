# D-Tech CMS

D-Tech CMS is a full-stack educational content and assignment management system built with React, Laravel 12, Laravel Sanctum, and MySQL.

The platform provides role-specific functionality for administrators, teachers, and students:

- **Administrators** manage users and monitor assignments, submissions, and content.
- **Teachers** manage assignments and educational content, review submissions, and assign grades.
- **Students** view assignments and published content, upload submissions, and view grades.

This repository contains the React frontend and Laravel API backend.

## Table of Contents

- [Technology Stack](#technology-stack)
- [Repository Structure](#repository-structure)
- [Core Features](#core-features)
- [Architecture](#architecture)
- [Database](#database)
- [API Endpoints](#api-endpoints)
- [Role Permissions](#role-permissions)
- [Local Development Setup](#local-development-setup)
- [Environment Configuration](#environment-configuration)
- [Running the Application](#running-the-application)
- [Testing](#testing)
- [File Uploads](#file-uploads)
- [Security](#security)
- [Current Implementation Status](#current-implementation-status)
- [Known Limitations](#known-limitations)
- [Database Safety](#database-safety)
- [Deployment Checklist](#deployment-checklist)
- [Troubleshooting](#troubleshooting)
- [License](#license)

## Technology Stack

### Frontend

- React 19
- React Router 7
- Vite 8
- JavaScript with JSX
- CSS
- ESLint

### Backend

- PHP 8.2+
- Laravel 12
- Laravel Sanctum 4
- Laravel Validation
- Laravel Password Reset
- PHPUnit 11

### Database

- MySQL 8 for the intended application database
- SQLite may be used for isolated automated tests
- Laravel migrations and Eloquent ORM

## Repository Structure

```text
D-Tech-CMS/
├── CMS-backend/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   └── Middleware/
│   │   └── Models/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   ├── routes/
│   │   ├── api.php
│   │   └── web.php
│   ├── storage/
│   └── tests/
└── CMS-frontend/
    ├── public/
    └── src/
        ├── components/
        ├── context/
        ├── pages/
        ├── services/
        ├── App.jsx
        └── main.jsx
```

## Core Features

### Authentication

- User registration
- Email/password login
- Sanctum bearer-token authentication
- Authenticated user retrieval
- Logout and token revocation
- Password reset request and reset handling

### User Management

- Admin-only user listing
- Admin-only user creation
- Admin-only user updating
- Admin-only user deletion
- User roles: `admin`, `teacher`, and `student`

### Assignments

- Assignment creation, listing, viewing, updating, and deletion
- Assignment title and description
- Teacher ownership
- Due dates
- Ownership restrictions for teachers
- Multiple submissions per assignment and student

### Submissions

- Student file uploads
- PDF, DOC, DOCX, TXT, and ZIP validation
- Maximum upload size of 10 MB
- Private file storage
- Authorized file downloads
- Teacher and admin grading
- Optional grading feedback
- Submission timestamps

### Content Management

- Content creation and editing
- Draft and published status
- Category labels
- Author ownership
- Students can only view published content
- Teachers can manage their own content and view published content
- Administrators can manage all content

### Dashboards

- Administrator totals for users, assignments, submissions, and content
- Teacher totals for assignments, submissions, grades, and content
- Student totals for available assignments, submissions, grades, and published content

## Architecture

```text
React Frontend
      |
      | HTTP / JSON
      | Bearer token
      v
Laravel API
      |
      | Sanctum authentication
      | Role middleware
      | Controllers and validation
      v
Eloquent Models
      |
      v
MySQL Database
```

The frontend is a single-page React application. It communicates with Laravel through `/api` routes and stores the Sanctum bearer token in browser `localStorage`.

Backend authorization is enforced independently of frontend route protection.

## Database

### Intended Database

The application is intended to use MySQL:

```text
Database: cms_db
Host: 127.0.0.1
Port: 3306
```

Database credentials must be supplied through `CMS-backend/.env`.

### Main Tables

| Table | Purpose |
|---|---|
| `users` | Accounts and role data |
| `assignments` | Teacher-created assignments |
| `submissions` | Student assignment submissions |
| `contents` | Educational content |
| `personal_access_tokens` | Sanctum bearer tokens |
| `password_reset_tokens` | Password reset records |
| `cache` | Database cache |
| `cache_locks` | Database cache locks |
| `jobs` | Database queue jobs |
| `job_batches` | Laravel job batches |
| `failed_jobs` | Laravel failed queue jobs |

Database sessions are not required because the current session driver is file-based.

### Relationships

- A teacher owns many assignments.
- An assignment has many submissions.
- A student has many submissions.
- A user or teacher creates content through `author_id`.
- Assignment and submission deletion is controlled by RESTRICT foreign keys.

## API Endpoints

All API endpoints use `/api` as the base path.

### Authentication

| Method | Endpoint | Access |
|---|---|---|
| POST | `/register` | Public |
| POST | `/login` | Public |
| POST | `/forgot-password` | Public |
| POST | `/reset-password` | Public |
| GET | `/user` | Authenticated |
| POST | `/logout` | Authenticated |

### Dashboard

| Method | Endpoint | Access |
|---|---|---|
| GET | `/dashboard` | Authenticated |

### Users

| Method | Endpoint | Access |
|---|---|---|
| GET | `/users` | Admin |
| POST | `/users` | Admin |
| GET | `/users/{id}` | Admin |
| PUT/PATCH | `/users/{id}` | Admin |
| DELETE | `/users/{id}` | Admin |

### Assignments

| Method | Endpoint | Access |
|---|---|---|
| GET | `/assignments` | Authenticated |
| GET | `/assignments/{id}` | Authenticated |
| POST | `/assignments` | Admin or teacher |
| PUT/PATCH | `/assignments/{id}` | Admin or assignment owner |
| DELETE | `/assignments/{id}` | Admin or assignment owner |

### Submissions

| Method | Endpoint | Access |
|---|---|---|
| GET | `/submissions` | Authenticated, filtered by role |
| GET | `/submissions/{id}` | Student owner, assigned teacher, or admin |
| GET | `/submissions/{id}/download` | Student owner, assigned teacher, or admin |
| POST | `/submissions` | Student |
| PUT/PATCH | `/submissions/{id}` | Assigned teacher or admin |

### Contents

| Method | Endpoint | Access |
|---|---|---|
| GET | `/contents` | Authenticated, filtered by role |
| GET | `/contents/{id}` | Authenticated, subject to publication and ownership rules |
| POST | `/contents` | Admin or teacher |
| PUT/PATCH | `/contents/{id}` | Admin or content author |
| DELETE | `/contents/{id}` | Admin or content author |

## Role Permissions

| Capability | Admin | Teacher | Student |
|---|---:|---:|---:|
| Manage users | Yes | No | No |
| View all assignments | Yes | Owned assignments only | Available assignments |
| Create assignments | Yes | Yes | No |
| Grade submissions | Yes | Owned assignments | No |
| Submit assignments | No | No | Yes |
| Create content | Yes | Yes | No |
| Edit all content | Yes | No | No |
| Edit own content | Yes | Yes | No |
| View draft content | Yes | Own drafts and published content | No |
| View published content | Yes | Yes | Yes |

## Local Development Setup

### Requirements

- Windows, macOS, or Linux
- PHP 8.2 or newer
- Composer
- Node.js 20 or newer
- npm
- MySQL 8
- Git

### 1. Clone the Repository

```powershell
git clone <repository-url>
cd "D-Tech-CMS"
```

### 2. Install Backend Dependencies

```powershell
cd CMS-backend
composer install
```

### 3. Configure the Backend

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Update `CMS-backend/.env` with local database and application values.

Do not commit `.env`, database passwords, API credentials, or the application key.

### 4. Install Frontend Dependencies

```powershell
cd ..\CMS-frontend
npm install
```

Create `CMS-frontend/.env.local` if a custom API URL is required:

```dotenv
VITE_API_URL=http://127.0.0.1:8001/api
```

## Environment Configuration

### Backend Example

```dotenv
APP_NAME="D-Tech CMS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8001
FRONTEND_URL=http://127.0.0.1:5173

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cms_db
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=file

MAIL_MAILER=log
```

Use `MAIL_MAILER=log` for local password-reset testing. Configure a real mail provider before production.

### Frontend Example

```dotenv
VITE_API_URL=http://127.0.0.1:8001/api
```

## Running the Application

Open separate terminals.

### Backend

```powershell
cd CMS-backend
php artisan serve --host=127.0.0.1 --port=8001
```

Backend URL:

```text
http://127.0.0.1:8001
```

### Frontend

```powershell
cd CMS-frontend
npm run dev
```

Frontend URL:

```text
http://127.0.0.1:5173
```

## Testing

### Backend Tests

The PHPUnit configuration uses an isolated in-memory SQLite database.

```powershell
cd CMS-backend
php artisan test
```

Current feature coverage includes:

- Registration, login, logout, and authenticated user retrieval
- Password reset
- Admin user authorization
- Teacher assignment creation
- Student submission upload
- Submission grading
- Submission download authorization
- Content visibility
- Role-specific dashboards

### Frontend

```powershell
cd CMS-frontend
npm run lint
npm run build
```

### Dependency Audits

```powershell
cd CMS-frontend
npm audit --omit=dev
```

```powershell
cd CMS-backend
composer audit
```

### PHP Syntax

```powershell
Get-ChildItem CMS-backend\app, CMS-backend\routes, CMS-backend\tests -Recurse -Include *.php |
  ForEach-Object { php -l $_.FullName }
```

## File Uploads

Submissions are stored on the Laravel private disk:

```text
CMS-backend/storage/app/private/submissions/
```

Validation rules:

- Required file
- Maximum size: 10 MB
- Allowed extensions: PDF, DOC, DOCX, TXT, ZIP

Submission downloads are authorized by the backend. Students may download their own submission, the assigned teacher may download it, and administrators may download it.

Private submission files must not be placed in `public/storage` without access-control changes.

## Security

### Implemented

- Sanctum bearer-token authentication
- Backend role middleware
- Assignment and content ownership checks
- Submission ownership and assigned-teacher checks
- Password hashing
- Strong registration password validation
- Input validation
- Private file storage
- Hidden password fields on API user serialization
- Foreign-key restrict deletion behavior

### Recommended Before Production

- Replace or harden browser token storage
- Add login and password-reset throttling
- Configure CORS for the exact production frontend origin
- Configure trusted proxies
- Configure secure cookies or same-site authentication if moving to SPA cookies
- Add upload malware scanning for untrusted files
- Add signed or time-limited file download URLs
- Add audit logging for administrative actions
- Add security headers and a Content Security Policy
- Upgrade packages with known Composer advisories
- Add automated security and authorization tests

## Current Implementation Status

### Implemented

- Authentication workflows
- Role middleware
- User CRUD APIs
- Assignment CRUD APIs
- Submission upload, listing, grading, and download APIs
- Content CRUD APIs
- Role-specific dashboards
- React protected routes
- Live API-backed frontend modules
- Password reset API and frontend page

### Implemented but Still Requiring Isolated or Browser Testing

- Existing MySQL migration reconciliation
- User editing and deletion UI
- Assignment editing and deletion UI
- Submission grading UI
- Complete category management
- Browser end-to-end workflows

## Known Limitations

- Sanctum bearer tokens are stored in `localStorage`.
- The original MySQL database should not be migrated until isolated rehearsal and backup verification are complete.
- Composer dependencies contain security advisories that should be upgraded with compatibility testing.
- The existing dashboard UI is now partially API-backed, but some advanced management actions still require dedicated edit/delete controls.
- Password reset mail delivery requires production SMTP or another mail provider.
- The frontend submission workflow is basic and does not include progress indicators or a dedicated grading interface.
- No browser E2E or visual regression suite is currently configured.

## Database Safety

Never run destructive migration commands against the original database.

Do not use:

```text
php artisan migrate:fresh
php artisan migrate:refresh
php artisan migrate:rollback
```

Before applying migrations to `cms_db`:

1. Create a verified backup.
2. Test migrations against an isolated MySQL database.
3. Verify unsigned BIGINT conversion and `AUTO_INCREMENT`.
4. Verify foreign keys and indexes.
5. Verify migration history.
6. Verify all existing records and NULL values.
7. Restore and reconcile only after approval.

The SQL backup included in the repository must be preserved and not overwritten.

## Deployment Checklist

- [ ] Upgrade Composer dependencies
- [ ] Run isolated MySQL migration rehearsal
- [ ] Back up the production database
- [ ] Configure production environment variables
- [ ] Configure the correct frontend and backend URLs
- [ ] Configure CORS
- [ ] Configure SMTP or another mail provider
- [ ] Configure MySQL credentials securely
- [ ] Configure private file storage or an external object store
- [ ] Configure queue workers if database jobs are used
- [ ] Configure cache storage
- [ ] Configure HTTPS
- [ ] Configure Sanctum stateful domains or bearer-token policy
- [ ] Add login and reset throttling
- [ ] Run Laravel tests
- [ ] Run frontend lint and build
- [ ] Run browser end-to-end tests
- [ ] Run production dependency audits

## Troubleshooting

### Frontend Cannot Reach the Backend

Verify:

- Laravel is running on port `8001`.
- `VITE_API_URL` points to the correct `/api` base URL.
- CORS allows the frontend origin.

### Authentication Returns 401

- Verify the Sanctum bearer token exists in `localStorage`.
- Call `/api/login` again to obtain a new token.
- Check the Laravel `APP_KEY`.
- Check database connectivity.

### Database Migrations Fail

- Do not execute destructive migration commands.
- Verify database backup and migration history.
- Inspect existing NULL values, duplicate emails, enum values, and foreign-key references.
- Review reconciliation migration exceptions.

### Password Reset Emails Are Missing

Set `MAIL_MAILER` to an actual mail provider and configure valid SMTP credentials. In local development, use Laravel's log mail transport.

### Submission Upload Fails

Verify:

- The request is multipart form data.
- The assignment exists and has not passed its due date.
- The file is within the 10 MB limit.
- The file extension is PDF, DOC, DOCX, TXT, or ZIP.
- The private storage directory is writable.

## License

No project-specific license has been defined. Add an appropriate license before public distribution.
