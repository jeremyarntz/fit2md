<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Description\WorkoutDescription;

final readonly class WorkoutSummary
{
    /**
     * @param list<Segment> $segments
     */
    public function __construct(
        public Activity $activity,
        public ?WorkoutDescription $description = null,
        public array $segments = [],
    ) {
    }
}
