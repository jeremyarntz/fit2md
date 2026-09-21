<?php

declare(strict_types=1);

namespace App\Application;

final readonly class WorkoutInput
{
    /** @param list<string> $imagePaths */
    public function __construct(
        public string $activityPath,
        public ?string $descriptionPath = null,
        public array $imagePaths = [],
    ) {
    }
}
