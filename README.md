# Bible Reading Tracker

Bible Reading Tracker is a Laravel application for managing structured reading cohorts, training-first enrollment, leader hierarchy oversight, messaging, notifications, audits, and operational automation.

## Core capabilities

- Member-facing reading plan discovery, enrollment, progress, and history
- Training resources and locked reading start until training is complete
- Hierarchy-aware leadership monitoring and scoped reporting
- Admin workflows for users, hierarchies, audits, messaging templates, and automation
- In-app manual with role-appropriate guides
- Daily automation for reminders, digests, vacancy alerts, and plan lifecycle transitions

## Stack

- PHP 8.2+
- Laravel 12
- Livewire 3
- MySQL or SQLite
- Vite for frontend assets
- Database-backed queues, notifications, cache, and sessions by default

## Local setup

1. Install dependencies:
```bash
composer install
npm install
```
2. Create your environment file:
```bash
cp .env.example .env
php artisan key:generate
```
3. Configure your database in `.env`.
4. Run migrations:
```bash
php artisan migrate
```
5. Link public storage:
```bash
php artisan storage:link
```
6. Start the app:
```bash
composer run dev
```

## Testing

Run the full automated suite with:

```bash
php artisan test
```

## Production deployment

The full deployment checklist and runbook live in [docs/deployment.md](/home/casper/Developer/bible-reading-tracker/docs/deployment.md).

Minimum production requirements:

- `APP_ENV=production`
- `APP_DEBUG=false`
- a real mail transport
- a running queue worker
- a cron entry or supervisor-managed scheduler

Production build and release steps:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Required background processes:

- Queue worker:
```bash
php artisan queue:work --queue=default --tries=3
```
- Scheduler:
```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Operations notes

- Daily automation is queued through `automation:run-daily`.
- The scheduler is registered in `bootstrap/app.php`.
- Notifications and message emails rely on the queue worker being healthy.
- The UI asset pipeline is fully local through Vite; no external icon, chart, avatar, or Alpine CDNs are required.
