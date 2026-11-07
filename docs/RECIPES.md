# Cadence recipes

## Delivery calendar after a pause

Persist the September14 anchor, interval 2 and Unit::Week. To show October deliveries after a pause query the original Schedule between October1 and November1. Results remain October12/26; do not build a new schedule from the last actual delivery. Apply host skip/holiday/capacity records to that proposed list and retain an occurrence ID/revision for deduplication.

```php
$schedule = new Schedule(CarbonImmutable::parse($subscription->anchor, $subscription->timezone), Unit::from($subscription->unit), $subscription->interval);
$proposed = $schedule->between($windowStart, $windowEnd, limit: 100);
```

The host must parse the anchor consistently with how it was stored (local wall time plus named timezone, or a retained aware instant). Billing cycles remain the provider's authoritative periods. A cart/basket does not replace the subscription/delivery snapshot.

## Month-end maintenance

```php
$schedule = new Schedule(CarbonImmutable::parse('2028-01-31 09:00', 'America/Phoenix'), Unit::Month);
$dates = $schedule->between($schedule->anchor, $schedule->at(3), limit: 4);
```

This yields Jan 31, Feb 29, Mar 31, Apr 30 at 09:00. Generate domain work orders under a host scheduler lease and unique `(schedule_revision, occurrence_identity)` key. Mark a completed/skipped occurrence on the work order, leaving the anchor intact. If approval stages are needed, start a Flow/Workflow on the saved work order; Cadence computes dates and the workflow handles review, with workspace policy supplied by the application.

## Recheck a strict change deadline

```php
$deadline = Cutoff::at($deliveryInstant, 48);
if (! Cutoff::isOpen($deliveryInstant, 48, CarbonImmutable::now())) {
    throw new DomainException('The change deadline has passed.');
}
```

Run the check again in the authorized locked change action. Show the deadline with its timezone to the user. At exact equality the action is closed; test one second before/at/after. Across DST the interval is48 elapsed hours, not “two local calendar days.” No package transaction, permissions or queue prevents a stale browser from submitting after cutoff.
