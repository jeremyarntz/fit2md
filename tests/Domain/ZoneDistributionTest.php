<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\Sample;
use App\Domain\Zone;
use App\Domain\ZoneDistribution;
use App\Domain\ZoneProfile;
use PHPUnit\Framework\TestCase;

final class ZoneDistributionTest extends TestCase
{
    public function testCountsSecondsBetweenSamplesInEachZone(): void
    {
        $profile = new ZoneProfile([new Zone('Low', 100), new Zone('High')]);
        $start = new \DateTimeImmutable('2026-01-01 00:00:00');

        $distribution = ZoneDistribution::fromSamples([
            new Sample($start, 90),
            new Sample($start->modify('+2 seconds'), 150),
            new Sample($start->modify('+5 seconds'), 150),
        ], $profile);

        self::assertSame(['name' => 'Low', 'seconds' => 2], $distribution->entries[0]);
        self::assertSame(['name' => 'High', 'seconds' => 3], $distribution->entries[1]);
    }
}
