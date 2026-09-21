<?php

declare(strict_types=1);

namespace App\Analysis;

use App\Domain\Activity;
use App\Domain\Description\Block;
use App\Domain\Description\WorkoutDescription;
use App\Domain\HeartRateStats;
use App\Domain\Lap;
use App\Domain\Segment;
use App\Domain\ZoneDistribution;
use App\Domain\ZoneProfile;

final readonly class SegmentBuilder
{
    public function __construct(
        private ZoneProfileProvider $zoneProfiles,
        private bool $firstLapIsWarmup = true,
    ) {
    }

    /** @return list<Segment> */
    public function build(Activity $activity, ?WorkoutDescription $description): array
    {
        $profile = $this->zoneProfiles->profile();
        $laps = $activity->laps;
        $blocks = $description->blocks ?? [];
        $segments = [];

        if ($this->firstLapIsWarmup && [] !== $laps) {
            $segments[] = $this->segment('Warmup', $activity, array_shift($laps), null, $profile);
        }

        $count = max(count($laps), count($blocks));

        for ($i = 0; $i < $count; ++$i) {
            $block = $blocks[$i] ?? null;

            $segments[] = $this->segment(
                $block->name ?? sprintf('Lap %d', $i + 1),
                $activity,
                $laps[$i] ?? null,
                $block,
                $profile,
            );
        }

        return $segments;
    }

    private function segment(string $label, Activity $activity, ?Lap $lap, ?Block $block, ?ZoneProfile $profile): Segment
    {
        if (null === $lap) {
            return new Segment(label: $label, block: $block);
        }

        $samples = $activity->samplesBetween($lap->startTime, $lap->endTime);

        return new Segment(
            label: $label,
            lap: $lap,
            heartRate: HeartRateStats::fromSamples($samples),
            zones: null !== $profile ? ZoneDistribution::fromSamples($samples, $profile) : null,
            block: $block,
        );
    }
}
