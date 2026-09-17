<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Sample
{
    public function __construct(
        public \DateTimeImmutable $timestamp,
        public ?int $heartRate = null,
        public ?int $cadence = null,
        public ?float $distanceMeters = null,
    ) {
    }
}
