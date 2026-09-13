<?php

use Carbon\CarbonImmutable as Date;
use MrNewport\LaravelCadence\{Schedule, Unit, Cutoff};

it('keeps month ends anchored and recovers leap day', function () {
    $schedule = new Schedule(Date::parse('2028-01-31 09:00', 'America/Phoenix'), Unit::Month);
    expect(array_map(fn ($d) => $d->format('Y-m-d H:i'), $schedule->between($schedule->anchor, Date::parse('2028-04-30 10:00', 'America/Phoenix'))))
        ->toBe(['2028-01-31 09:00', '2028-02-29 09:00', '2028-03-31 09:00', '2028-04-30 09:00']);
    $annual = new Schedule(Date::parse('2028-02-29'), Unit::Year);
    expect($annual->at(1)->toDateString())->toBe('2029-02-28')->and($annual->at(4)->toDateString())->toBe('2032-02-29');
});

it('retains original cadence after a skipped date or a resumed window', function () {
    $schedule = new Schedule(Date::parse('2026-09-14', 'America/Phoenix'), Unit::Week, 2);
    $dates = $schedule->between(Date::parse('2026-09-29', 'America/Phoenix'), Date::parse('2026-11-01', 'America/Phoenix'));
    expect(array_map(fn ($d) => $d->toDateString(), $dates))->toBe(['2026-10-12', '2026-10-26']);
    expect($schedule->anchor->toDateString())->toBe('2026-09-14');
});

it('keeps wall clock time across daylight saving changes', function () {
    foreach (['2026-03-01', '2026-10-25'] as $anchor) {
        $schedule = new Schedule(Date::parse($anchor.' 09:00', 'America/New_York'));
        expect($schedule->at(1)->format('H:i'))->toBe('09:00');
        expect($schedule->at(1)->offset)->not->toBe($schedule->anchor->offset);
    }
});

it('treats the change deadline as strict and uses elapsed hours across DST', function () {
    $delivery = Date::parse('2026-03-09 00:00', 'America/New_York');
    $cutoff = Cutoff::at($delivery, 24);
    expect($delivery->getTimestamp() - $cutoff->getTimestamp())->toBe(86400);
    expect($cutoff->format('Y-m-d H:i'))->toBe('2026-03-07 23:00');
    expect(Cutoff::isOpen($delivery, 24, $cutoff))->toBeFalse()
        ->and(Cutoff::isOpen($delivery, 24, $cutoff->subSecond()))->toBeTrue();
});

it('uses inclusive windows and returns nothing before the anchor', function () {
    $anchor = Date::parse('2026-01-31 09:00', 'UTC');
    $schedule = new Schedule($anchor, Unit::Month);
    $feb = $schedule->at(1);
    expect($schedule->between($feb, $feb))->toHaveCount(1);
    expect($schedule->nextOnOrAfter($feb->addSecond())->toDateString())->toBe('2026-03-31');
    expect($schedule->between($anchor->subMonth(), $anchor->subSecond()))->toBe([]);
    expect($schedule->nextOnOrAfter($anchor->setTimezone('America/Phoenix'))->eq($anchor))->toBeTrue();
});

it('bounds large windows without silently dropping occurrences', function () {
    $schedule = new Schedule(Date::parse('2026-01-01'), Unit::Day);
    expect(fn () => $schedule->between($schedule->anchor, $schedule->anchor->addDays(10), 3))->toThrow(LengthException::class);
    expect(fn () => new Schedule($schedule->anchor, Unit::Week, 0))->toThrow(InvalidArgumentException::class);
    expect(fn () => Cutoff::at($schedule->anchor, -1))->toThrow(InvalidArgumentException::class);
});

it('registers a read only Artisan preview and rejects normalized invalid dates', function () {
    $this->artisan('cadence:preview 2026-01-31 --unit=month --count=3')->assertSuccessful();
    $this->artisan('cadence:preview 2026-02-31')->assertFailed();
});
