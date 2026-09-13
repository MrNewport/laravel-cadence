<?php

namespace MrNewport\LaravelCadence;

use Carbon\CarbonImmutable;

/** Calendar occurrences always derive from the original anchor, never the previous occurrence. */
final readonly class Schedule
{
    public function __construct(public CarbonImmutable $anchor, public Unit $unit = Unit::Week, public int $interval = 1)
    {
        if ($interval < 1 || $interval > 10000) {
            throw new \InvalidArgumentException('Interval must be between 1 and 10000.');
        }
    }

    public function at(int $index): CarbonImmutable
    {
        if ($index < 0 || $index > intdiv(PHP_INT_MAX, $this->interval)) {
            throw new \InvalidArgumentException('Occurrence index is out of range.');
        }
        $amount = $index * $this->interval;
        return match ($this->unit) {
            Unit::Day => $this->anchor->addDays($amount),
            Unit::Week => $this->anchor->addWeeks($amount),
            Unit::Month => $this->anchor->addMonthsNoOverflow($amount),
            Unit::Year => $this->anchor->addYearsNoOverflow($amount),
        };
    }

    public function nextOnOrAfter(CarbonImmutable $from): CarbonImmutable
    {
        return $this->at($this->indexOnOrAfter($from));
    }

    /** @return list<CarbonImmutable> Inclusive window; throws instead of silently truncating. */
    public function between(CarbonImmutable $from, CarbonImmutable $to, int $limit = 10000): array
    {
        if ($from->gt($to) || $limit < 1 || $limit > 100000) {
            throw new \InvalidArgumentException('Use an ordered window and a limit between 1 and 100000.');
        }
        $dates = [];
        for ($index = $this->indexOnOrAfter($from); ($date = $this->at($index))->lte($to); $index++) {
            if (count($dates) >= $limit) {
                throw new \LengthException('The recurrence window exceeds the requested limit.');
            }
            $dates[] = $date;
        }
        return $dates;
    }

    private function indexOnOrAfter(CarbonImmutable $from): int
    {
        if ($from->lte($this->anchor)) {
            return 0;
        }
        $from = $from->setTimezone($this->anchor->getTimezone());
        $distance = match ($this->unit) {
            Unit::Day => $this->anchor->diffInDays($from),
            Unit::Week => $this->anchor->diffInWeeks($from),
            Unit::Month => $this->anchor->diffInMonths($from),
            Unit::Year => $this->anchor->diffInYears($from),
        };
        $index = max(0, (int) floor($distance / $this->interval));
        while ($index > 0 && $this->at($index - 1)->gte($from)) {
            $index--;
        }
        while ($this->at($index)->lt($from)) {
            $index++;
        }
        return $index;
    }
}
