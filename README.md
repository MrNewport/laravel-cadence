# Laravel Cadence

Anchored calendar recurrence and delivery change cutoffs. Built for subscription deliveries and recurring property maintenance where skipping a date must not move every later occurrence.

Supports Laravel 12 on PHP 8.2+ and Laravel 13 on PHP 8.3+. Uses Carbon 3 for date calculations. No tables, scheduled jobs, network requests, or paid services.

```sh
composer require mrnewport/laravel-cadence:^1.0
php artisan cadence:preview 2026-01-31 --unit=month --count=4 --timezone=America/Phoenix
```

Until Packagist indexes the package, add the GitHub repository as a Composer VCS source:

```sh
composer config repositories.cadence vcs https://github.com/MrNewport/laravel-cadence
```

## Calendar rules

```php
use Carbon\CarbonImmutable;
use MrNewport\LaravelCadence\{Schedule, Unit, Cutoff};

$schedule = new Schedule(
    CarbonImmutable::parse('2026-09-14 00:00', 'America/Phoenix'),
    Unit::Week,
    interval: 2,
);

$dates = $schedule->between(
    CarbonImmutable::parse('2026-10-01', 'America/Phoenix'),
    CarbonImmutable::parse('2026-11-01', 'America/Phoenix'),
);
// October 12 and 26: the September anchor remains unchanged.

$next = $schedule->nextOnOrAfter(CarbonImmutable::now());
$editable = Cutoff::isOpen($next, hoursBefore: 48);
```

Units are day, week, month, and year. Every occurrence is calculated from the original anchor. January 31 produces February 28/29 and then March 31. A February 29 yearly anchor returns to February 29 in the next leap year. Calendar time retains the anchor's timezone and wall-clock time across DST. A nonexistent local time uses Carbon/PHP's forward normalization; ambiguous local times follow Carbon/PHP's timezone rules. For fixed elapsed intervals, use an elapsed-time scheduler instead.

Windows include both endpoints. `nextOnOrAfter` is inclusive. Occurrences before the anchor are never returned. `between` defaults to a 10,000-result limit and throws if exceeded; it never silently truncates. A cutoff is an elapsed number of hours before the occurrence, computed in UTC and returned in its timezone. Changes are closed exactly at the cutoff.

Persist the anchor, unit, interval, and timezone in your application's existing subscription or maintenance records. Apply skips, pauses, capacity limits, holidays, billing, and authorization in your own domain service. Do not replace the anchor with the last delivered date.

## Tests

`composer test` covers month ends, leap years, original cadence after resume, spring/fall DST, exact cutoff boundaries, inclusive windows, resource bounds, and Artisan discovery. CI tests Laravel 12/13 and a fresh Laravel 13 installation without development dependencies.
