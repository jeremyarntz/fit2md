<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class ZoneProfile
{
    /**
     * @param non-empty-list<Zone> $zones ordered low to high; the last may have no max
     */
    public function __construct(
        public array $zones,
    ) {
        if ([] === $zones) {
            throw new \InvalidArgumentException('A zone profile needs at least one zone.');
        }
    }

    /** Which zone (by position in the list) a heart rate falls into. */
    public function indexFor(int $bpm): int
    {
        foreach ($this->zones as $index => $zone) {
            if (null === $zone->maxBpm || $bpm <= $zone->maxBpm) {
                return $index;
            }
        }

        return count($this->zones) - 1;
    }
}
