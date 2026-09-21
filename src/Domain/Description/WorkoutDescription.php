<?php

declare(strict_types=1);

namespace App\Domain\Description;

final readonly class WorkoutDescription
{
    /**
     * @param list<Block> $blocks
     */
    public function __construct(
        public ?string $title = null,
        public ?string $coach = null,
        public ?string $location = null,
        public ?int $rpe = null,
        public ?string $notes = null,
        public array $blocks = [],
    ) {
    }
}
