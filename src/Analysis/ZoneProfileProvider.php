<?php

declare(strict_types=1);

namespace App\Analysis;

use App\Domain\Zone;
use App\Domain\ZoneProfile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ZoneProfileProvider
{
    /**
     * @param list<array{name: string, max: int|null}> $zones
     */
    public function __construct(
        #[Autowire(env: 'json:FIT2MD_ZONES')]
        private array $zones,
    ) {
    }

    /** Null when no zones are configured — zones are optional. */
    public function profile(): ?ZoneProfile
    {
        if ([] === $this->zones) {
            return null;
        }

        return new ZoneProfile(array_map(
            fn (array $zone) => new Zone($zone['name'], $zone['max']),
            $this->zones,
        ));
    }
}
