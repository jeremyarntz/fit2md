<?php

declare(strict_types=1);

namespace App\Application;

use App\Analysis\SegmentBuilder;
use App\Description\DescriptionParserInterface;
use App\Domain\WorkoutSummary;
use App\Parser\ActivityParserRegistry;

final readonly class SummarizeWorkout
{
    public function __construct(
        private ActivityParserRegistry $parsers,
        private DescriptionParserInterface $descriptionParser,
        private SegmentBuilder $segmentBuilder,
    ) {
    }

    public function summarize(WorkoutInput $input): WorkoutSummary
    {
        $activity = $this->parsers->parserFor($input->activityPath)->parse($input->activityPath);

        $description = null !== $input->descriptionPath
            ? $this->descriptionParser->parse($input->descriptionPath)
            : null;

        return new WorkoutSummary(
            activity: $activity,
            description: $description,
            segments: $this->segmentBuilder->build($activity, $description),
        );
    }
}
