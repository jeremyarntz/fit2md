<?php

declare(strict_types=1);

namespace App\Tests\Description\Text;

use App\Description\Text\ExerciseLineParser;
use App\Domain\Description\Exercise;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExerciseLineParserTest extends TestCase
{
    #[DataProvider('lines')]
    public function testParsesLine(string $line, ?Exercise $expected): void
    {
        self::assertEquals($expected, (new ExerciseLineParser())->parse($line));
    }

    /** @return iterable<string, array{string, ?Exercise}> */
    public static function lines(): iterable
    {
        yield 'reddit style with reps and weight' => [
            '8 x deadlift (slow) | 75#',
            new Exercise('deadlift (slow)', reps: 8, weight: 75.0, weightUnit: 'lb'),
        ];

        yield 'reddit style without reps' => [
            'Skier swing | 25#',
            new Exercise('Skier swing', weight: 25.0, weightUnit: 'lb'),
        ];

        yield 'rounds and RPE as extras' => [
            '8 x pullover | 35 lbs | 2 rounds | RPE 7',
            new Exercise('pullover', sets: 2, reps: 8, weight: 35.0, weightUnit: 'lb', rpe: 7),
        ];

        yield 'unknown extra becomes a note' => [
            '8 x deadlift | 50# | used bench',
            new Exercise('deadlift', reps: 8, weight: 50.0, weightUnit: 'lb', note: 'used bench'),
        ];

        yield 'lifting notation' => [
            'Deadlift 2x8 @ 50 lb RPE 7',
            new Exercise('Deadlift', sets: 2, reps: 8, weight: 50.0, weightUnit: 'lb', rpe: 7),
        ];

        yield 'lifting notation, bodyweight' => [
            'Push-up 3x15',
            new Exercise('Push-up', sets: 3, reps: 15),
        ];

        yield 'kilograms' => [
            'Squat 5x5 @ 60 kg',
            new Exercise('Squat', sets: 5, reps: 5, weight: 60.0, weightUnit: 'kg'),
        ];

        yield 'plain instruction stays text' => [
            '2.5 min tread',
            null,
        ];

        yield 'anchor with only a note stays text' => [
            'Anchor: 4 x broad jump to burpee | note: used bench',
            null,
        ];
    }
}
