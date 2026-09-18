<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class HeartRateStats
{
    private function __construct(
        public ?int $min,
        public ?int $max,
        public ?int $average,
    ) {
    }

    /** @param list<Sample> $samples */
    public static function fromSamples(array $samples): self
    {
        $rates = [];

        foreach ($samples as $sample) {
            if (null !== $sample->heartRate) {
                $rates[] = $sample->heartRate;
            }
        }

        if ([] === $rates) {
            return new self(null, null, null);
        }

        return new self(
            min($rates),
            max($rates),
            (int) round(array_sum($rates) / count($rates)),
        );
    }
}
