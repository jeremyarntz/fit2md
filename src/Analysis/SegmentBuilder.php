<?php

declare(strict_types=1);

namespace App\Analysis;

use App\Domain\Activity;
use App\Domain\Description\WorkoutDescription;
use App\Domain\Segment;

final readonly class SegmentBuilder
{
    public function __construct(
        private bool $firstLapIsWarmup = true,
    ) {
    }

    /** @return list<Segment> */
    public function build(Activity $activity, ?WorkoutDescription $description): array
    {
        $laps = $activity->laps;
        $blocks = $description->blocks ?? [];
        $segments = [];

        if ($this->firstLapIsWarmup && [] !== $laps) {
            $warmup = array_shift($laps);
            $segments[] = new Segment(
                label: 'Warmup',
                lap: $warmup,
                heartRate: $activity->heartRateStatsForLap($warmup),
            );
        }

        $count = max(count($laps), count($blocks));

        for ($i = 0; $i < $count; ++$i) {
            $lap = $laps[$i] ?? null;
            $block = $blocks[$i] ?? null;

            $segments[] = new Segment(
                label: $block->name ?? sprintf('Lap %d', $i + 1),
                lap: $lap,
                heartRate: null !== $lap ? $activity->heartRateStatsForLap($lap) : null,
                block: $block,
            );
        }

        return $segments;
    }
}
