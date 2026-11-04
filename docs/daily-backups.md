# Automatic daily database backups

Both schedules run daily at 03:00 Asia/Singapore (19:00 UTC the previous day). They do not depend on a browser, a signed-in administrator, or the Backup page being open. Each environment records its own last success/error in `tbl_settings`; Database Backup displays that status. Duplicate invocations for the same Singapore calendar day are skipped. A database lease prevents overlapping runs and expires after 30 minutes if a process is interrupted.

## Local Windows / XAMPP

Run `scripts/install_daily_backup.ps1` from an elevated PowerShell session. It registers **SharonStore Daily Database Backup**, running `C:\xampp\php\php.exe scripts/daily_backup.php` as SYSTEM. It catches up after missed runs and retries failures three times at 30-minute intervals. The PC and MySQL must be running; Apache is not needed. Default XAMPP database configuration is used unless database environment variables have been configured for the task's account.

For an immediate check, run `C:\xampp\php\php.exe scripts/daily_backup.php`. A nonzero exit code means the backup failed. Daily files use `backups/sharonstore_auto_local_*.sql`; the job retains the five newest daily files and leaves other file types and manual/safety backups alone. Installers on a machine using another timezone must choose an equivalent local trigger time.

## Vercel production

`vercel.json` schedules `/api/backup_cron.php` with `0 19 * * *`. Cron activates after production deployment. Add a random production environment variable **CRON_SECRET** (at least 32 random bytes encoded as hex), then redeploy. Vercel sends it in `Authorization: Bearer ...`; requests without the matching secret are rejected before any database connection. Preview deployments do not run the production cron schedule. Vercel's plan-dependent timing precision applies.

Configure the existing Google Drive Apps Script integration on Database Backup, or supply **BACKUP_GDRIVE_WEBHOOK_URL** as a production environment variable. Cloud jobs require a successful Drive upload before recording success: files in `/tmp` are temporary and do not count as durable cloud backups. The existing webhook's five-file retention policy is used. A failed attempt remains eligible for another invocation that day; Vercel does not automatically retry failed cron requests.

Database credentials are the existing `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, and `DB_NAME` variables. No database passwords or cron secrets belong in source control. Scheduled exports omit ephemeral sessions, fail if any table cannot be exported, use a repeatable-read transaction for InnoDB data, and publish the SQL file only after the entire export succeeds.

## Verification

Run `C:\xampp\php\php.exe tests/scheduled_backup.php` with `TEST_DB_DSN` pointing at an isolated MySQL/MariaDB test database. `TEST_DB_USER` and `TEST_DB_PASS` are optional. Tests refuse a database name without the `sharonstore_test_` prefix. Do not run restore tests against store data.
