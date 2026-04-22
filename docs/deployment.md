# Deployment Runbook

## Pre-deploy checklist

- Confirm the working tree is clean and the intended commit is tagged or recorded.
- Confirm `php artisan test` passes on the release candidate.
- Confirm new migrations have been reviewed for runtime impact.
- Confirm production `.env` values are ready:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL`
  - database credentials
  - mail credentials
  - queue/cache/session settings

## Build

1. Install PHP dependencies:
```bash
composer install --no-dev --optimize-autoloader
```
2. Install frontend dependencies:
```bash
npm ci
```
3. Build assets:
```bash
npm run build
```

## Release steps

1. Put the app into maintenance mode if your deploy strategy needs it:
```bash
php artisan down
```
2. Run database migrations:
```bash
php artisan migrate --force
```
3. Ensure public storage is linked:
```bash
php artisan storage:link
```
4. Warm framework caches:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
5. Bring the app back up:
```bash
php artisan up
```

## Background processes

The application is not production-ready without both of these running.

### Queue worker

Required for:

- message recipient email delivery
- queued automation cycles
- queued automation notifications

Example command:

```bash
php artisan queue:work --queue=default --tries=3 --timeout=120
```

Use Supervisor, systemd, or your platform worker manager to keep it alive.

### Scheduler

Required for:

- `reading:check-progress`
- `automation:run-daily`

Recommended cron entry:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

Verify registration after deploy:

```bash
php artisan schedule:list
```

## Post-deploy smoke test

Run these checks after every release:

1. Open `/up` and confirm the health endpoint returns successfully.
2. Log in as a member and confirm dashboard, manual, messages, and notifications load.
3. Log in as an admin and confirm admin dashboard, user directory, hierarchy screens, and reports load.
4. Run:
```bash
php artisan schedule:list
```
   Confirm both daily tasks appear.
5. Trigger a manual automation run from the admin UI and confirm:
   - the action returns immediately
   - a queue worker picks up the job
   - database notifications are created
6. Confirm outbound mail works by sending a test message or observing a queued recipient email complete successfully.

## Rollback notes

- If code must be rolled back, verify whether any new migrations are backward compatible before restoring a previous release.
- Clear and rebuild caches after rollback:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Troubleshooting

### Automation is not running

- Check `php artisan schedule:list`
- Check cron or scheduler service logs
- Check queue worker health

### Notifications are not arriving

- Confirm queue workers are running
- Check `jobs` and `failed_jobs`
- Verify `notifications` table exists and migrations are current

### Message emails are not being sent

- Confirm mail transport credentials
- Check queued `DeliverMessageRecipientEmail` jobs
- Review `failed_jobs` and application logs
