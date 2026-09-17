<?php

declare(strict_types=1);

namespace App\Parser\Fit;

use adriangibbons\phpFITFileAnalysis;
use App\Domain\Activity;
use App\Domain\Lap;
use App\Domain\Sample;
use App\Parser\ActivityParserInterface;
use App\Parser\Exception\ActivityParseException;

final class FitActivityParser implements ActivityParserInterface
{
    public function supports(string $path): bool
    {
        return 'fit' === strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    public function parse(string $path): Activity
    {
        if (!is_readable($path)) {
            throw new ActivityParseException(sprintf('Cannot read file "%s".', $path));
        }

        try {
            $fit = new phpFITFileAnalysis($path);
        } catch (\Throwable $e) {
            throw new ActivityParseException(sprintf('Failed to decode FIT file "%s".', $path), previous: $e);
        }

        /** @var array<string, mixed> $messages */
        $messages = $fit->data_mesgs;

        $samples = $this->buildSamples($messages['record'] ?? []);

        if ([] === $samples) {
            throw new ActivityParseException(sprintf('FIT file "%s" contains no usable records.', $path));
        }

        $session = $messages['session'] ?? [];

        if (!is_array($session)) {
            $session = [];
        }

        return new Activity(
            startedAt: $samples[0]->timestamp,
            samples: $samples,
            laps: $this->buildLaps($messages['lap'] ?? []),
            sport: $this->intOrNull($this->firstValue($session, 'sport')),
            subSport: $this->intOrNull($this->firstValue($session, 'sub_sport')),
        );
    }

    /**
     * @return list<Sample>
     */
    private function buildSamples(mixed $record): array
    {
        if (!is_array($record)) {
            return [];
        }

        $heartRate = $this->column($record, 'heart_rate');
        $cadence = $this->column($record, 'cadence');
        $distance = $this->column($record, 'distance');

        $timestamps = array_keys($heartRate + $cadence + $distance);
        sort($timestamps);

        $samples = [];

        foreach ($timestamps as $timestamp) {
            if (!is_int($timestamp)) {
                continue;
            }

            $samples[] = new Sample(
                timestamp: $this->toDateTime($timestamp),
                heartRate: $this->intOrNull($heartRate[$timestamp] ?? null),
                cadence: $this->intOrNull($cadence[$timestamp] ?? null),
                distanceMeters: $this->floatOrNull($distance[$timestamp] ?? null),
            );
        }

        return $samples;
    }

    /**
     * @return list<Lap>
     */
    private function buildLaps(mixed $lapMessages): array
    {
        if (!is_array($lapMessages)) {
            return [];
        }

        $starts = $this->column($lapMessages, 'start_time');
        $ends = $this->column($lapMessages, 'timestamp');
        $timers = $this->column($lapMessages, 'total_timer_time');
        $avgHr = $this->column($lapMessages, 'avg_heart_rate');
        $maxHr = $this->column($lapMessages, 'max_heart_rate');

        $laps = [];

        foreach (array_keys($starts) as $index) {
            $start = $this->intOrNull($starts[$index] ?? null);
            $end = $this->intOrNull($ends[$index] ?? null);

            if (null === $start || null === $end) {
                continue;
            }

            $laps[] = new Lap(
                index: is_int($index) ? $index : count($laps),
                startTime: $this->toDateTime($start),
                endTime: $this->toDateTime($end),
                timerSeconds: $this->floatOrNull($timers[$index] ?? null) ?? (float) ($end - $start),
                avgHeartRate: $this->intOrNull($avgHr[$index] ?? null),
                maxHeartRate: $this->intOrNull($maxHr[$index] ?? null),
            );
        }

        return $laps;
    }

    /**
     * The library returns a scalar when a message occurs once, an array otherwise.
     *
     * @param array<array-key, mixed> $message
     *
     * @return array<array-key, mixed>
     */
    private function column(array $message, string $field): array
    {
        $value = $message[$field] ?? null;

        if (null === $value) {
            return [];
        }

        return is_array($value) ? $value : [0 => $value];
    }

    /** @param array<array-key, mixed> $message */
    private function firstValue(array $message, string $field): mixed
    {
        $column = $this->column($message, $field);

        return [] === $column ? null : reset($column);
    }

    private function toDateTime(int $unixSeconds): \DateTimeImmutable
    {
        return new \DateTimeImmutable('@'.$unixSeconds, new \DateTimeZone('UTC'));
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_int($value) || is_float($value) ? (int) $value : null;
    }

    private function floatOrNull(mixed $value): ?float
    {
        return is_int($value) || is_float($value) ? (float) $value : null;
    }
}
