<?php

declare(strict_types=1);

namespace App\Tests\Parser\Fit;

use App\Parser\Exception\ActivityParseException;
use App\Parser\Fit\FitActivityParser;
use PHPUnit\Framework\TestCase;

final class FitActivityParserTest extends TestCase
{
    private const FIXTURES = __DIR__.'/../../Fixtures';

    public function testParsesCorosFile(): void
    {
        $activity = (new FitActivityParser())->parse(self::FIXTURES.'/coros_otf.fit');

        self::assertCount(4, $activity->laps);
        self::assertCount(3044, $activity->samples);
        self::assertNull($activity->samples[0]->heartRate);
        self::assertSame(87, $activity->samples[1]->heartRate);
        self::assertSame(122, $activity->laps[1]->avgHeartRate);
    }

    public function testParsesLapTimesInUtc(): void
    {
        $activity = (new FitActivityParser())->parse(self::FIXTURES.'/tread_50.fit');

        self::assertCount(5, $activity->laps);
        self::assertSame(1789693235, $activity->laps[0]->startTime->getTimestamp());
        self::assertSame(0, $activity->laps[0]->startTime->getOffset());
        self::assertEqualsWithDelta(745.5, $activity->laps[4]->timerSeconds, 0.1);
    }

    public function testSupportsFitFilesOnly(): void
    {
        $parser = new FitActivityParser();

        self::assertTrue($parser->supports('workout.fit'));
        self::assertTrue($parser->supports('WORKOUT.FIT'));
        self::assertFalse($parser->supports('notes.txt'));
    }

    public function testMissingFileThrows(): void
    {
        $this->expectException(ActivityParseException::class);

        (new FitActivityParser())->parse(self::FIXTURES.'/does-not-exist.fit');
    }
}
