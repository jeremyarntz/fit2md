<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Description\WorkoutDescription;

final readonly class WorkoutSummary
{
    public function __construct(
        public Activity $activity,
        public ?WorkoutDescription $description = null,
    ) {
    }
}
