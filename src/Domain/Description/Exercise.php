<?php

declare(strict_types=1);

namespace App\Domain\Description;

final readonly class Exercise
{
    public function __construct(
        public string $name,
        public ?int $sets = null,
        public ?int $reps = null,
        public ?float $weight = null,
        public ?string $weightUnit = null,
        public ?int $rpe = null,
        public ?string $note = null,
    ) {
    }
}