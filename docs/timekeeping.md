# Application timekeeping

The business calendar and displayed times use Philippine time (`Asia/Manila`, UTC+08:00).

- `includes/time.php` initializes PHP's timezone and supplies shared timestamp and SQL calendar helpers.
- Database session timezones and saved records are preserved. Existing database datetimes are interpreted by that database using `UNIX_TIMESTAMP`, then converted for Philippine display and calendar grouping. Changing the database session timezone would reinterpret old `DATETIME` values, so the application does not do that.
- Checkout stores a single sale instant and returns that same Unix timestamp plus an ISO date with an explicit `+08:00` offset. Receipts use the saved sale time rather than the time the print dialog opens.
- Dashboard totals, reports and their item breakdowns, forecasting months, expiry checks, account creation dates, and shift history use Philippine dates. ISO weeks use `YEARWEEK(..., 3)` for MySQL/TiDB compatibility.
- `api/index.php?action=server_time` serves the read-only, uncached clock before session/database startup. Its handler is in `includes/time_endpoint.php`, so it reuses the existing router function and keeps the deployment within the Hobby plan's twelve-function limit.
- `assets/js/time.js` synchronizes the browser with the server on load, every minute, and on returning to the page. The clock advances using a monotonic timer between synchronizations, including during temporary connection failures. Browser timezone and device wall-clock changes do not determine displayed business time.
- Expiry values remain calendar dates. Report generation timestamps refresh immediately before printing. A shift's start time remains the saved event time.
- Backup status and filenames use the same timezone. The existing daily schedules remain at approximately 03:00 Philippine/Singapore time (`19:00 UTC` for the cloud schedule); this change does not alter scheduler settings.

Historical imports that were originally labelled with the wrong timezone cannot be repaired unambiguously without knowing their source. This implementation does not rewrite those records or guess an offset for old backup filenames.

## Verification

`tests/system_time.php` requires `TEST_DB_DSN` naming an isolated `sharonstore_test_` database. It uses temporary tables and tests UTC and UTC+08 database sessions across Philippine midnight, ISO-week, month and year boundaries, including account dates, report breakdowns, forecast grouping, and stored/receipt timestamp agreement.

`node tests/system_time.cjs` checks UTC, Manila and Los Angeles browser environments with an intentionally incorrect device clock. It covers synchronization, offline clock progression, expiry dates, saved receipt timestamps, print generation times and shift date filters.

These checks and the existing regression suites passed locally on November 9, 2026. Local browser verification against a disposable UTC database confirmed that Shift History and Live Summary agree, and that a sale after Philippine midnight appears in the correct daily report and item breakdown. Deployment is not established by these local checks.
