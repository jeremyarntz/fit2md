<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Activity
{
    /**
     * @param non-empty-list<Sample> $samples ordered by timestamp, ascending
     * @param list<Lap>              $laps
     */
    public function __construct(
        public \DateTimeImmutable $startedAt,
        public array $samples,
        public array $laps,
        public ?int $sport = null,
        public ?int $subSport = null,
    ) {
        if ([] === $samples) {
            throw new \InvalidArgumentException('An activity must have at least one sample.');
        }

        $previous = null;

        foreach ($samples as $sample) {
            if (null !== $previous && $sample->timestamp < $previous) {
                throw new \InvalidArgumentException('Samples must be ordered by timestamp.');
            }

            $previous = $sample->timestamp;
        }
    }

    public function firstSample(): Sample
    {
        return $this->samples[0];
    }

    public function lastSample(): Sample
    {
        return $this->samples[count($this->samples) - 1];
    }

    public function durationSeconds(): int
    {
        return $this->lastSample()->timestamp->getTimestamp()
            - $this->firstSample()->timestamp->getTimestamp();
    }

    /**
     * Samples falling in [$from, $to) — inclusive start, exclusive end.
     *
     * @return list<Sample>
     */
    public function samplesBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $found = [];

        foreach ($this->samples as $sample) {
            if ($sample->timestamp >= $to) {
                break;
            }

            if ($sample->timestamp >= $from) {
                $found[] = $sample;
            }
        }

        return $found;
    }

    public function heartRateStats(): HeartRateStats
    {
        return HeartRateStats::fromSamples($this->samples);
    }

    public function heartRateStatsForLap(Lap $lap): HeartRateStats
    {
        return HeartRateStats::fromSamples($this->samplesBetween($lap->startTime, $lap->endTime));
    }
}
