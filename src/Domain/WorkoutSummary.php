<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class WorkoutSummary
{
    public function __construct(
        public Activity $activity,
        public ?string $title = null,
        public ?string $coach = null,
        public ?string $location = null,
        public ?int $rpe = null,
        public ?string $notes = null,
    ) {
    }
}
