# Pulse — project tracker & weekly progress report

Teams post one short update per project each week; Pulse turns it into the weekly
PDF / PowerPoint report automatically. Built on **Laravel 13 + SQLite**, a **Vue 3** front end,
and a small **Python** script for report rendering.

## What it does

| Area | What people do there |
| --- | --- |
| **Dashboard** | RAG counts, who still owes an update this week, projects needing attention, production issues, milestones due / overdue. Filter by programme, step through past weeks. |
| **Projects** | Programme → project, phase, owner, team, target date, milestones (inline status changes). |
| **Weekly update** | RAG status, headline, critical-path items, achievements, next steps, support needed, metrics (e.g. UAT coverage / pass rate). **Pre-filled from last week** so people only change what moved. Amber/red requires a critical-path reason. |
| **Issues & risks** | Production issues, issues, risks, dependencies — severity, owner (person, team or vendor), due date, latest progress. Overdue items flagged. |
| **Weekly report** | Preview what goes in, then download **PDF** (for email) or **PowerPoint** (editable, for the meeting). Also auto-generated every Friday 16:00. |
| **People** | Users and roles (admin only). |

### Roles
`admin` everything · `pmo` all projects + reports · `owner` manages own projects · `contributor` posts updates/milestones/issues on projects they're a member of · `viewer` read-only.
All checks live in `app/Models/User.php` (`canEditProject`, `canManageProject`).

## Setup

Requirements: PHP 8.3+ (pdo_sqlite), Composer, Node 20+, Python 3.10+.

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed          # demo data based on the RDB tracker email
npm install && npm run build
pip install -r reports/requirements.txt
php artisan serve                   # http://localhost:8000
```

Demo logins (password `Pulse@2026` — change them, or `migrate` without `--seed` for a clean start):
`admin@pulse.local` (admin), `abdallah@pulse.local` (PMO), `patrick@pulse.local` (owner),
`grace@pulse.local` (contributor), `director@pulse.local` (viewer).

For development run `npm run dev` alongside `php artisan serve`.

## Weekly report

- In the app: **Weekly report → Download PDF / PowerPoint**.
- CLI: `php artisan pulse:report --format=pdf --week=2026-09-28 --programme="Digital Lending (SASA)"`
- Scheduled: Friday 16:00 (PDF) and 16:05 (PPTX) — add the Laravel scheduler to cron:
  `* * * * * cd /path/to/pulse && php artisan schedule:run >> /dev/null 2>&1`

Files are kept in `storage/app/reports/` and listed under *Recent reports*.
Laravel builds the snapshot (`app/Services/PortfolioSnapshot.php`) and passes it as JSON to
`reports/generate_report.py`. Test the renderer on its own with:
`python3 reports/generate_report.py --input reports/sample_snapshot.json --output test.pdf --format pdf`

Branding: `PULSE_ORG_NAME`, `PULSE_REPORT_TITLE`, `PULSE_BRAND_COLOR` in `.env`. If Python isn't
on the PATH as `python3`, set `PULSE_PYTHON` (e.g. `C:\Python312\python.exe`).

## Customising sign-in

Auth is a pluggable driver — set `PULSE_AUTH_DRIVER` in `.env`:

- `database` (default) — email + password stored in Pulse.
- `ldap` — Active Directory bind (needs `php-ldap`; configure `LDAP_*` in `.env`). New directory
  users are auto-created with `PULSE_AUTO_PROVISION_ROLE` (set empty to require an admin to add them first).
  Local **admin** accounts can always sign in with their Pulse password as a break-glass route.
- Your own (Azure AD, a bank SSO API…): create a class implementing
  `App\Auth\Contracts\Authenticator` (one method: `attempt($login, $password): ?User`) and register it in
  `config/pulse.php` under `auth.drivers`.

## Scaling notes

- Switch from SQLite to MySQL / Postgres / SQL Server by changing `DB_*` in `.env` — no code changes.
- Lists (phases, statuses, severities, roles) are in `app/Support/Pulse.php`.
- The front end talks only to `/api/*` (routes in `routes/web.php`), so a mobile app or Power BI
  can use the same endpoints later.
- Report generation can be moved onto a queue if it gets slow (it's ~1s today).

## Project layout

```
app/Auth/                 pluggable sign-in drivers
app/Http/Controllers/Api  JSON API
app/Models                Project, Milestone, WeeklyUpdate, Issue, ReportRun, User
app/Services              PortfolioSnapshot (weekly view), ReportGenerator (calls Python)
config/pulse.php          branding, auth driver, report settings
reports/                  generate_report.py + requirements + sample data
resources/js              Vue 3 app (pages/, components/)
```
