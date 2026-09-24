<?php

declare(strict_types=1);

namespace App\Tests\Normalization;

use App\Description\Text\PlainTextDescriptionParser;
use PHPUnit\Framework\TestCase;

final class NormalizedFixtureTest extends TestCase
{
    public function testNormalizedFixtureParsesIntoFourBlocks(): void
    {
        $description = (new PlainTextDescriptionParser())
            ->parse(__DIR__.'/../Fixtures/raw_reddit_post.normalized.txt');

        // Header: title and personal fields, with "Limited by" folded into Notes.
        self::assertSame('HYROX Phase 1 Week 2 2G', $description->title);
        self::assertSame('Alex', $description->coach);
        self::assertSame('My Studio', $description->location);
        self::assertSame(8, $description->rpe);
        self::assertSame('Felt strong on deadlifts. Limited by grip on the row.', $description->notes);

        // Four blocks, in order, with their planned minutes.
        self::assertSame(
            ['Tread Block 1', 'Tread Block 2', 'Floor Block 1', 'Row Block 1'],
            array_map(fn ($block) => $block->name, $description->blocks),
        );
        self::assertSame(
            [14.5, 7.5, 14.5, 7.5],
            array_map(fn ($block) => $block->durationMinutes, $description->blocks),
        );

        // Repetition condensed: Tread Block 1 went from 11 lines to 3.
        $tread1 = $description->blocks[0];
        self::assertCount(3, $tread1->lines);
        self::assertSame('4 rounds: 2.5 min tread + 30 sec surge', $tread1->lines[0]->text);

        // The odd "tread + tread" pair was kept, not merged into the pattern.
        self::assertSame('75 sec tread + 15 sec tread', $description->blocks[1]->lines[1]->text);
    }
}
