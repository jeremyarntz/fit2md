<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class ZoneDistribution
{
    /** A strap dropout shouldn't count as minutes in one zone. */
    private const MAX_GAP_SECONDS = 10;

    /**
     * @param list<array{name: string, seconds: int}> $entries
     */
    private function __construct(
        public array $entries,
    ) {
    }

    /**
     * @param list<Sample> $samples
     */
    public static function fromSamples(array $samples, ZoneProfile $profile): self
    {
        $seconds = array_fill(0, count($profile->zones), 0);
        $count = count($samples);

        for ($i = 0; $i < $count - 1; ++$i) {
            $current = $samples[$i];

            if (null === $current->heartRate) {
                continue;
            }

            $gap = $samples[$i + 1]->timestamp->getTimestamp() - $current->timestamp->getTimestamp();
            $seconds[$profile->indexFor($current->heartRate)] += min($gap, self::MAX_GAP_SECONDS);
        }

        $entries = [];

        foreach ($profile->zones as $index => $zone) {
            $entries[] = ['name' => $zone->name, 'seconds' => $seconds[$index]];
        }

        return new self($entries);
    }
}
