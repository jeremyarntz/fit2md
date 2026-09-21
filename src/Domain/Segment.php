<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Description\Block;

final readonly class Segment
{
    public function __construct(
        public string $label,
        public ?Lap $lap = null,
        public ?HeartRateStats $heartRate = null,
        public ?Block $block = null,
    ) {
        if (null === $lap && null === $block) {
            throw new \InvalidArgumentException('A segment needs a lap, a block, or both.');
        }
    }
}
