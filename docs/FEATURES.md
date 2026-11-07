# Cadence feature index

| Public API → return | Contract / errors | Guide |
| --- | --- | --- |
| `new Schedule(CarbonImmutable $anchor, Unit $unit = Unit::Week, int $interval = 1)` | Final readonly anchor/unit/interval;1–10,000 interval or InvalidArgumentException. | [Definition](HANDBOOK.md#define-a-calendar-schedule) |
| `Schedule::at(int $index): CarbonImmutable` | Index 0anchor; original-anchor no-overflow arithmetic; negative/overflow index → InvalidArgumentException. | [Calendar semantics](HANDBOOK.md#month-ends-leap-years-and-dst) |
| `nextOnOrAfter(CarbonImmutable $from): CarbonImmutable` | Inclusive, never before anchor; returned anchor timezone. | [Queries](HANDBOOK.md#query-inclusive-windows) |
| `between(CarbonImmutable $from, CarbonImmutable $to, int $limit = 10000): array` | Inclusive list; ordered window/limit1–100,000 or InvalidArgumentException; overflow results → LengthException. | [Queries](HANDBOOK.md#query-inclusive-windows) |
| `Unit::Day/Week/Month/Year` | Backing day/week/month/year; standard enum from/tryFrom. | [Definition](HANDBOOK.md#define-a-calendar-schedule) |
| `Cutoff::at(CarbonImmutable, int $hoursBefore): CarbonImmutable` | UTC elapsed-hour subtraction/restored timezone; negative → InvalidArgumentException. | [Deadlines](HANDBOOK.md#cutoff-deadlines) |
| `Cutoff::isOpen(CarbonImmutable, int, ?CarbonImmutable $now = null): bool` | Strictly before deadline; equality closed. | [Deadlines](HANDBOOK.md#cutoff-deadlines) |
| `cadence:preview anchor --unit= --every= --count= --timezone=` | ISO JSON list; real date/unit/timezone/count1–100 validation; FAILURE on bad args. | [Command](HANDBOOK.md#artisan-preview) |
| CadenceServiceProvider | Autodiscovers command; no storage/jobs/routes/assets. | [Install](HANDBOOK.md#install-and-preview) |
| Anchor persistence, skips/pauses/DST, resource bounds, work uniqueness | Host scheduling/ownership/billing responsibilities. | [Adoption](HANDBOOK.md#persistence-and-adoption), [recipes](RECIPES.md) |

Executable examples: [ScheduleTest](../tests/Feature/ScheduleTest.php). The package has no hidden configuration or migration API; the table covers its complete functional public surface.
