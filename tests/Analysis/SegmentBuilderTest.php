<?php

declare(strict_types=1);

namespace App\Tests\Analysis;

use App\Analysis\SegmentBuilder;
use App\Analysis\ZoneProfileProvider;
use App\Domain\Activity;
use App\Domain\Description\Block;
use App\Domain\Description\WorkoutDescription;
use App\Domain\Lap;
use App\Domain\Sample;
use App\Domain\Segment;
use PHPUnit\Framework\TestCase;

final class SegmentBuilderTest extends TestCase
{
    public function testWarmupThenBlocksInOrder(): void
    {
        $segments = $this->builder()->build($this->activity(3), $this->description('A', 'B'));

        self::assertSame(['Warmup', 'A', 'B'], $this->labels($segments));
        self::assertSame(1, $segments[1]->lap?->index);
    }

    public function testExtraLapsAreNumbered(): void
    {
        $segments = $this->builder()->build($this->activity(4), $this->description('A'));

        self::assertSame(['Warmup', 'A', 'Lap 2', 'Lap 3'], $this->labels($segments));
        self::assertNull($segments[3]->block);
    }

    public function testExtraBlocksHaveNoHeartRate(): void
    {
        $segments = $this->builder()->build($this->activity(2), $this->description('A', 'B'));

        self::assertSame(['Warmup', 'A', 'B'], $this->labels($segments));
        self::assertNull($segments[2]->lap);
        self::assertNull($segments[2]->heartRate);
    }

    public function testWithoutDescription(): void
    {
        $segments = $this->builder()->build($this->activity(2), null);

        self::assertSame(['Warmup', 'Lap 1'], $this->labels($segments));
    }

    public function testWarmupCanBeTurnedOff(): void
    {
        $builder = new SegmentBuilder(new ZoneProfileProvider([]), firstLapIsWarmup: false);
        $segments = $builder->build($this->activity(2), $this->description('A', 'B'));

        self::assertSame(['A', 'B'], $this->labels($segments));
    }

    public function testZonesOnlyWhenConfigured(): void
    {
        $withoutZones = $this->builder()->build($this->activity(1), null);
        self::assertNull($withoutZones[0]->zones);

        $withZones = (new SegmentBuilder(new ZoneProfileProvider([
            ['name' => 'Low', 'max' => 120],
            ['name' => 'High', 'max' => null],
        ])))->build($this->activity(1), null);
        self::assertNotNull($withZones[0]->zones);
    }

    private function builder(): SegmentBuilder
    {
        return new SegmentBuilder(new ZoneProfileProvider([]));
    }

    /** An activity with the given number of 60-second laps, one sample per second. */
    private function activity(int $lapCount): Activity
    {
        $start = new \DateTimeImmutable('2026-01-01 00:00:00');
        $samples = [new Sample($start, 100)];

        for ($second = 1; $second <= $lapCount * 60; ++$second) {
            $samples[] = new Sample($start->modify(sprintf('+%d seconds', $second)), 100);
        }

        $laps = [];

        for ($i = 0; $i < $lapCount; ++$i) {
            $laps[] = new Lap(
                index: $i,
                startTime: $start->modify(sprintf('+%d seconds', $i * 60)),
                endTime: $start->modify(sprintf('+%d seconds', ($i + 1) * 60)),
                timerSeconds: 60.0,
            );
        }

        return new Activity(startedAt: $start, samples: $samples, laps: $laps);
    }

    private function description(string ...$names): WorkoutDescription
    {
        return new WorkoutDescription(
            blocks: array_map(fn (string $name) => new Block($name, []), array_values($names)),
        );
    }

    /**
     * @param list<Segment> $segments
     *
     * @return list<string>
     */
    private function labels(array $segments): array
    {
        return array_map(fn (Segment $segment) => $segment->label, $segments);
    }
}
