# Agent guide

Date: 2025-10-28

This repo implements a plan-centric Bible reading tracker using Laravel 11 + Livewire. Use this guide to contribute consistently.

## Core concepts

-   Users join Reading Plans via pivot table `user_reading_plans` with fields: joined_date (date), current_day (int), current_streak (int), completion_rate (decimal), is_active (bool).
-   Daily readings (reading/break cadence) are generated from plan settings: chapters_per_day, streak_days, break_days, start_date.
-   Reading completion is recorded in `reading_progress` (unique per user,daily_reading).

## Conventions

-   Prefer plan-centric logic (ReadingPlan + DailyReading), not testament-day helpers.
-   Use `joined_date` (date-only) in pivot; avoid `joined_at` in new code.
-   For “current day”, compute from plan start_date and clamp to max day in plan.
-   Completion rate: base on past reading days up to current_day (exclude future + break days) when feasible.
-   Admin routes guarded by `App\Http\Middleware\Admin` and `User::isAdmin()`.

## Development checklist

-   Migrations: `daily_readings` is created with all required columns. Later “add” migrations are idempotent (guarded by hasColumn).
-   Controllers/routes: avoid referencing legacy methods (`DashboardController::progress/statistics`, testament-based helpers). Use Livewire components or plan-centric controller logic.
-   Views: avoid hardcoded metrics; derive from pivot (e.g., current streak).
-   Relations: avoid duplicate relation names. Use `DailyReading::readingProgress()`.

## Common tasks

-   Generate plan readings: `php artisan reading:generate {plan_id}`
-   Backfill chapter links: `php artisan reading:link-chapters`
-   Check/advance progress (cron): `php artisan reading:check-progress`

## Testing ideas (TBD)

-   Generation creates expected counts with correct break cadence.
-   Marking completion updates pivot streak and completion_rate.
-   Nightly job advances on break days and resets streak after long gaps.

## Roadmap hints

-   Clean up remaining legacy code, unify completion rate calc, add group messaging UI, and seed demo data.
