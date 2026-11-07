# Laravel Cadence

Calendar occurrences anchored to an original date and strict elapsed-hour change cutoffs. Month ends, leap years and local wall time remain deliberate; skips, capacity, billing and job creation stay in the host.

**[Interactive workshop](https://matthewnewport.com/packages/laravel-cadence/lab)** — Try guided examples in your browser.

```sh
composer require mrnewport/laravel-cadence:^1.0
```

If your Composer registry has not indexed the GitHub tag, add its VCS source and repeat the requirement:

```sh
composer config repositories.laravel-cadence vcs https://github.com/MrNewport/laravel-cadence
composer require mrnewport/laravel-cadence:^1.0
```

No tables or scheduled jobs are created. Try `php artisan cadence:preview 2026-01-31 --unit=month --count=4 --timezone=America/Phoenix`.

Start with the [handbook](docs/HANDBOOK.md) for fresh Laravel 12/13 installation and the complete feature/operational guide. The [feature index](docs/FEATURES.md) links every public API, return contract, failure mode and executable reference. The [recipes](docs/RECIPES.md) show serious standalone and optional cross-package application use.

Laravel's own PHP runtime requirements apply. Package dependency constraints and current test coverage are described in the handbook. Run `composer test` in a development checkout; optional browser/renderer checks are documented with their requirements. Installing this package does not automatically integrate another package or authorize application records.
