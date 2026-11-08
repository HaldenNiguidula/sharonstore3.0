<?php
declare(strict_types=1);

const APP_TIMEZONE = 'Asia/Manila';
date_default_timezone_set(APP_TIMEZONE);

function appTime(?int $timestamp = null): DateTimeImmutable {
    return (new DateTimeImmutable('@' . ($timestamp ?? time())))->setTimezone(new DateTimeZone(APP_TIMEZONE));
}

/** Interpret existing database datetimes in their database session timezone, then display PHT. */
function appLocalSql(string $column): string {
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $column)) {
        throw new InvalidArgumentException('Invalid timestamp column.');
    }
    return "TIMESTAMPADD(SECOND, UNIX_TIMESTAMP({$column}) + 28800, '1970-01-01 00:00:00')";
}

function appTodaySql(): string {
    return 'DATE(UTC_TIMESTAMP() + INTERVAL 8 HOUR)';
}

function appIsoWeekSql(string $column): string {
    $week = 'YEARWEEK(' . appLocalSql($column) . ', 3)';
    // ISO week-year without relying on DATE_FORMAT %x/%v support in the database.
    return "CONCAT(FLOOR({$week} / 100), '-', LPAD(MOD({$week}, 100), 2, '0'))";
}
