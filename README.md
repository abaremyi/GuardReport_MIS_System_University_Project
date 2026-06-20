# GuardReport — Security Incident Reporting System

A PHP MVC web application for security companies and institutions to digitise their incident reporting workflow. Guards submit reports with evidence; supervisors review and escalate; administrators analyse trends.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.0+ |
| MySQL | 8.0+ |
| Apache | 2.4+ (with mod_rewrite) |
| Composer | 2.x |
| XAMPP (dev) | 8.x recommended |

---

## Quick Start (XAMPP / Local)

### 1. Clone / copy project

```
C:\xampp\htdocs\guardreport\
```

### 2. Install PHP dependencies

```bash
cd C:\xampp\htdocs\guardreport
composer install
```

### 3. Create the database

Open **phpMyAdmin** → run `config/guardreport_db.sql`.

Or via CLI:
```bash
mysql -u root -p < config/guardreport_db.sql
```

### 4. Configure environment

Copy `.env` and fill in your values:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=guardreport_db
DB_USER=root
DB_PASS=

JWT_SECRET_KEY=your_very_long_random_secret_here_min_32_chars

SMTP_USER=your_gmail@gmail.com
SMTP_PASS=your_gmail_app_password
```

> **Gmail App Password:** Enable 2FA on your Google account → Google Account → Security → App Passwords → generate one for "Mail".

### 5. Create uploads folder

```bash
mkdir -p uploads/users uploads/evidence
chmod 755 uploads
```

### 6. Open in browser

```
http://localhost/guardreport/admin/dashboard
```

**Default super-admin credentials:**
- Email: `admin@guardreport.rw`
- Password: `Admin@1234`

> **Change this password immediately after first login.**

---

## Project Structure

```
guardreport/
├── config/
│   ├── config.php          # App + JWT + SMTP constants
│   ├── database.php        # PDO singleton
│   ├── paths.php           # ROOT_PATH, BASE_URL, helper functions
│   └── guardreport_db.sql  # Full database schema + seed data
│
├── helpers/
│   ├── admin-base.php      # Auth guard for every admin page
│   ├── AuthMiddleware.php  # JWT validation + permission check
│   ├── JWTHandler.php      # Sign / verify JWT tokens
│   ├── PermissionHelper.php# hasPermission(), statusBadge(), severityBadge()
│   └── UploadHelper.php    # Evidence + user photo upload handler
│
├── layouts/
│   ├── admin-head.php      # HTML head, CSS, fonts
│   ├── admin-nav.php       # Top navigation + JS interactions
│   └── admin-scripts.php   # Footer scripts, SweetAlert, </main></body></html>
│
├── css/
│   └── portal.css          # Full design system (Navy/Red security theme)
│
├── modules/
│   ├── Authentication/     # Login, users, roles
│   │   ├── api/            authApi.php, userApi.php, roleApi.php
│   │   ├── controllers/    AuthController.php, RoleController.php
│   │   ├── models/         UserModel.php
│   │   └── views/          login.php, dashboard.php, users-management.php, 404.php
│   │
│   ├── Incidents/          # Core reporting module
│   │   ├── api/            incidentApi.php, evidenceApi.php
│   │   ├── controllers/    IncidentController.php
│   │   ├── models/         IncidentModel.php
│   │   └── views/          incidents.php, incident-create.php, incident-view.php
│   │
│   ├── Sites/              # Client premise management
│   │   ├── api/            siteApi.php
│   │   ├── models/         SiteModel.php
│   │   └── views/          sites.php
│   │
│   ├── Shifts/             # Guard scheduling
│   │   ├── api/            shiftApi.php
│   │   ├── models/         ShiftModel.php
│   │   └── views/          shifts.php
│   │
│   └── Reports/            # Analytics dashboard
│       ├── api/            reportApi.php
│       └── views/          reports.php
│
├── uploads/
│   ├── users/              # Profile photos
│   └── evidence/           # Incident evidence files
│
├── vendor/                 # Composer packages (auto-generated)
├── .env                    # Environment variables (never commit)
├── .htaccess               # URL rewriting + security headers
├── composer.json           # PHP dependencies
└── index.php               # Main router
```

---

## User Roles

| Role | Description |
|---|---|
| **Super Admin** | Full unrestricted access. Cannot be deleted. |
| **Administrator** | Manages sites, users, shifts, views all incidents, exports reports. |
| **Supervisor** | Reviews incidents at assigned sites, updates status, view reports. |
| **Guard** | Submits incident reports, uploads evidence, views own submissions and shifts. |

---

## API Reference

All endpoints return `Content-Type: application/json`. Authentication is via `auth_token` cookie (JWT).

### Auth — `/api/auth`
| Method | ?action= | Description |
|---|---|---|
| POST | login | Authenticate, sets cookie |
| POST | logout | Clears cookie |
| POST | register | Create pending account |
| POST | verify-registration-otp | Verify email OTP |
| POST | forgot-password | Send reset OTP |
| POST | verify-otp | Verify reset OTP |
| POST | reset-password | Set new password |
| POST | heartbeat | Extend session |

### Incidents — `/api/incidents`
| Method | ?action= | Description |
|---|---|---|
| GET | list | Paginated + filtered list |
| GET | get&id=N | Full incident detail |
| GET | types | Incident type list |
| GET | stats | Summary counts |
| POST | create | Submit new incident |
| POST | update&id=N | Edit incident |
| POST | status&id=N | Change status + notes |
| DELETE | (id=N) | Delete incident |

### Evidence — `/api/incidents/evidence`
| Method | Description |
|---|---|
| POST `?incident_id=N` | Upload files (multipart/form-data, field: `files[]`) |
| DELETE `?id=N` | Remove evidence file |

### Sites — `/api/sites`
| Method | ?action= | Description |
|---|---|---|
| GET | list | All sites |
| GET | get&id=N | Site detail |
| POST | create | Add site |
| POST | update&id=N | Edit site |
| DELETE | (id=N) | Delete site |

### Shifts — `/api/shifts`
| Method | ?action= | Description |
|---|---|---|
| GET | list | Filtered shift list |
| GET | my-shifts | Guard's upcoming shifts |
| POST | create | Schedule shift |
| POST | update&id=N | Edit shift |
| DELETE | (id=N) | Cancel shift |

### Reports — `/api/reports`
| Method | ?action= | Description |
|---|---|---|
| GET | summary | KPI counts |
| GET | trend | Monthly line chart data |
| GET | by-severity | Doughnut chart data |
| GET | by-type | Bar chart data |
| GET | by-site | Horizontal bar data |
| GET | guard-activity | Guard submission table |

---

## Development Roadmap

- **Phase 1** ✅ Foundation (DB, Auth, Layouts, CSS)
- **Phase 2** ✅ Incidents Module (CRUD, Evidence, Status workflow)
- **Phase 3** ✅ Sites & Shifts (Location management, Scheduling)
- **Phase 4** ✅ Reports & Analytics (Charts, Guard activity)
- **Phase 5** 🔲 Notifications, Profile page, Roles UI, Mobile audit, Export PDF/Excel

---

## Security Notes

- JWT tokens expire in **45 minutes** (`exp = time() + 2700`)
- Passwords hashed with **bcrypt** (PHP `PASSWORD_BCRYPT`)
- Evidence files validated by **MIME type** (not just extension)
- `.htaccess` blocks direct access to `config/`, `helpers/`, `vendor/`
- All SQL uses **PDO prepared statements** — no raw interpolation
- Super-admin account is protected from deletion and status change by non-super-admins

---

## License

Academic project — University of Kigali. All rights reserved.