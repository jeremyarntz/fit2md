<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\WorkoutSummary;
use App\Parser\ActivityParserRegistry;

final readonly class SummarizeWorkout
{
    public function __construct(
        private ActivityParserRegistry $parsers,
    ) {
    }

    public function summarize(string $activityPath): WorkoutSummary
    {
        $activity = $this->parsers->parserFor($activityPath)->parse($activityPath);

        return new WorkoutSummary(activity: $activity);
    }
}
