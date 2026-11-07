# Laravel Cadence handbook

Cadence calculates calendar occurrences from their original anchor and elapsed-hour change deadlines. It is independent of billing providers, database storage and queue scheduling. Skipping or pausing an occurrence does not move later dates.

## Install and preview

```sh
composer create-project laravel/laravel planner-app '^12.0'
cd planner-app
composer require mrnewport/laravel-cadence:^1.0
php artisan cadence:preview 2026-01-31 --unit=month --count=4 --timezone=America/Phoenix
```

Use `'^13.0'` for Laravel 13 with its supported PHP runtime; Laravel 12 / PHP 8.2+ is supported. If the tag is not indexed by your registry, add `composer config repositories.cadence vcs https://github.com/MrNewport/laravel-cadence` and repeat the requirement. Laravel discovers CadenceServiceProvider and its read-only Artisan command. There are no migrations, routes, network services, scheduled jobs, cloud bindings, frontend assets or package configuration file. The PHP classes can be called from any bootstrapped application with the declared dependencies.

## Define a calendar schedule

```php
use Carbon\CarbonImmutable;
use MrNewport\LaravelCadence\{Schedule, Unit, Cutoff};

$anchor = CarbonImmutable::parse('2026-09-14 09:00', 'America/Phoenix');
$schedule = new Schedule($anchor, Unit::Week, interval: 2);
$first = $schedule->at(0); // anchor
$third = $schedule->at(2); // 2026-10-12 09:00 America/Phoenix
```

`Schedule` is a final readonly value object. Public properties are anchor (CarbonImmutable), unit (Unit) and interval (int). Constructor defaults are Unit::Week and interval 1. Intervals must be 1–10,000; invalid values throw InvalidArgumentException. Use Unit enum cases Day/Week/Month/Year with backing values day/week/month/year. `Unit::from($string)` throws ValueError for unknown text; `tryFrom` can support a host validation boundary.

`at(int $index): CarbonImmutable` computes anchor + index ×interval calendar units. Index 0 is the anchor; negative or multiplication-overflow indexes throw InvalidArgumentException. Each calculation starts from the original anchor. It does not mutate the anchor or reuse a previous clamped date. Date-library/runtime representable bounds still apply; choose practical application horizons rather than extreme indexes.

### Month ends, leap years and DST

Months/years use Carbon no-overflow arithmetic. A January31 monthly anchor yields February28/29 then March31. A leap-day yearly anchor yields February28 in ordinary years and returns to February29 in a later leap year. Days/weeks/months/years retain the anchor's timezone and wall-clock intent. They are not fixed elapsed-second intervals across daylight-saving changes.

A nonexistent local time follows Carbon/PHP forward normalization; an ambiguous time follows its timezone rules. Avoid inventing a second timezone policy in callers. If the domain must reject ambiguous/nonexistent times, validate anchor selection explicitly before constructing Schedule. For elapsed time such as every168hours use a different domain contract.

## Query inclusive windows

```php
$dates = $schedule->between(
    CarbonImmutable::parse('2026-10-01 00:00', 'America/Phoenix'),
    CarbonImmutable::parse('2026-11-01 23:59', 'America/Phoenix'),
    limit: 100,
);
// October12 and26, both 09:00
$next = $schedule->nextOnOrAfter(CarbonImmutable::parse('2026-10-12 09:00', 'America/Phoenix'));
// October12 itself
```

`between(CarbonImmutable $from, CarbonImmutable $to, int $limit = 10000): array` includes both endpoints and returns a list of CarbonImmutable occurrences in order. No occurrences before the anchor are returned; a wholly earlier window gives an empty array. Invalid reversed windows or limits outside 1–100,000 throw InvalidArgumentException. If the result would exceed the limit it throws LengthException instead of silently truncating. Keep windows/limits bounded before serializing dates or dispatching jobs; the return list is held in memory.

`nextOnOrAfter(CarbonImmutable $from): CarbonImmutable` is inclusive. An instant at/before anchor returns anchor. A query one second after an occurrence advances to the next. Query instants in different timezones are compared as instants and normalized for schedule indexing; results keep the anchor timezone. If you need an exclusive query, pass your explicit instant-after boundary rather than assuming “next” excludes equality.

## Cutoff deadlines

```php
$delivery = CarbonImmutable::parse('2026-03-09 00:00', 'America/New_York');
$deadline = Cutoff::at($delivery, hoursBefore: 24);
// 2026-03-07 23:00 local: exactly86,400 elapsed seconds before delivery
$open = Cutoff::isOpen($delivery, 24, $deadline); // false
```

`Cutoff::at(CarbonImmutable $occurrence, int $hoursBefore): CarbonImmutable` subtracts elapsed hours in UTC, then restores the occurrence timezone. Negative hours throw InvalidArgumentException; zero means the occurrence itself. `isOpen(CarbonImmutable, int, ?CarbonImmutable $now = null): bool` uses the supplied instant or CarbonImmutable::now(). It requires now **strictly before** the deadline. At the exact cutoff changes are closed. Persist/communicate the timezone and exact deadline; do not infer a local-calendar-day cutoff from elapsed hours.

## Artisan preview

Signature: `cadence:preview {anchor} {--unit=week} {--every=1} {--count=10} {--timezone=UTC}`. Anchor must be an actual YYYY-MM-DD; invalid dates normalized by a date library are rejected. It starts at local midnight. every must be digit text and accepted by Schedule's1–10,000 bound; count is 1–100. The timezone must be valid and unit must be an enum backing value. Successful output is a JSON array of ISO8601 date-times including offsets. Errors return FAILURE with “Invalid recurrence” text. Preview only computes dates; it creates no rows/jobs and does not prove production delivery capacity.

```sh
php artisan cadence:preview 2028-02-29 --unit=year --every=1 --count=5 --timezone=UTC
```

## Persistence and adoption

Persist the original local anchor/timezone, unit and interval in your existing subscription/maintenance record. Store occurrence identities separately (for example schedule revision + occurrence index/date) and enforce uniqueness in the host. Skips, holidays, pauses, resumptions, capacity and actual fulfillment remain host state; never replace the anchor with the last completed/delivered date. Changing the intended anchor/cadence is a versioned domain change that needs explicit treatment of already generated work.

A read calculation grants no permission and reserves no capacity. For concurrent “generate due work” workers, use a host lease/lock and unique occurrence keys. Recompute/validate cutoffs in the authorized mutation transaction rather than trusting a browser's open flag. A provider's billing period is provider-owned and should not be guessed from a local recurrence.

See [recipes](RECIPES.md) for delivery calendars, maintenance windows and strict edit deadlines. [FEATURES.md](FEATURES.md) lists every public API. `composer test` covers anchored month/leap behavior, resume, DST, inclusive windows, cutoffs, limits and command discovery; [ScheduleTest](../tests/Feature/ScheduleTest.php) is executable source for the examples. On adoption verify existing anchors/timezones, paused/skipped records and deadline boundaries before scheduling real work.

## Optional composition

Stow may hold an editable integer-bundle selection before a delivery draft, while Cadence computes dates from the host's retained subscription anchor. Flow/Workflow may review maintenance work that the host generated for an occurrence. Fields may validate the user's proposed start date/interval/timezone. Those are explicit application compositions; Cadence does not register them or require any other MrNewport package.
