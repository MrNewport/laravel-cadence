<?php

namespace MrNewport\LaravelCadence;

use Carbon\CarbonImmutable;

final class Cutoff
{
    /** Subtract elapsed hours in UTC so DST does not turn 24 hours into 23 or 25. */
    public static function at(CarbonImmutable $occurrence, int $hoursBefore): CarbonImmutable
    {
        if ($hoursBefore < 0) {
            throw new \InvalidArgumentException('Cutoff hours must not be negative.');
        }
        return $occurrence->utc()->subHours($hoursBefore)->setTimezone($occurrence->getTimezone());
    }

    public static function isOpen(CarbonImmutable $occurrence, int $hoursBefore, ?CarbonImmutable $now = null): bool
    {
        return ($now ?? CarbonImmutable::now())->lt(self::at($occurrence, $hoursBefore));
    }
}
