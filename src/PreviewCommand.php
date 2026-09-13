<?php

namespace MrNewport\LaravelCadence;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class PreviewCommand extends Command
{
    protected $signature = 'cadence:preview {anchor : ISO date YYYY-MM-DD} {--unit=week} {--every=1} {--count=10} {--timezone=UTC}';
    protected $description = 'Preview anchored calendar occurrences without creating or changing records';

    public function handle(): int
    {
        try {
            $anchor = (string) $this->argument('anchor');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $anchor)) {
                throw new \InvalidArgumentException('Anchor must use YYYY-MM-DD.');
            }
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $anchor, (string) $this->option('timezone'));
            if ($date->format('Y-m-d') !== $anchor) {
                throw new \InvalidArgumentException('Anchor must be a valid calendar date.');
            }
            foreach (['every', 'count'] as $option) {
                if (!ctype_digit((string) $this->option($option))) {
                    throw new \InvalidArgumentException('Count and interval must be positive integers.');
                }
            }
            $count = (int) $this->option('count');
            if ($count < 1 || $count > 100) {
                throw new \InvalidArgumentException('Preview count must be between 1 and 100.');
            }
            $schedule = new Schedule($date, Unit::from($this->option('unit')), (int) $this->option('every'));
            $this->line(json_encode(array_map(fn ($index) => $schedule->at($index)->toIso8601String(), range(0, $count - 1)), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Invalid recurrence: '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
