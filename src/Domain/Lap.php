<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Lap
{
    public function __construct(
        public int $index,
        public \DateTimeImmutable $startTime,
        public \DateTimeImmutable $endTime,
        public float $timerSeconds,
        public ?int $avgHeartRate = null,
        public ?int $maxHeartRate = null,
    ) {
        if ($endTime < $startTime) {
            throw new \InvalidArgumentException('A lap cannot end before it starts.');
        }
    }
}
