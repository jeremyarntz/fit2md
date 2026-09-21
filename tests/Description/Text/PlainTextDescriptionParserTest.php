<?php

declare(strict_types=1);

namespace App\Tests\Description\Text;

use App\Description\Text\PlainTextDescriptionParser;
use PHPUnit\Framework\TestCase;

final class PlainTextDescriptionParserTest extends TestCase
{
    public function testParsesRedditStyleDescription(): void
    {
        $description = (new PlainTextDescriptionParser())
            ->parse(__DIR__.'/../../Fixtures/hyrox_p1w2.txt');

        self::assertSame('HYROX Phase 1 Week 2 2G', $description->title);
        self::assertSame('Natalie', $description->coach);
        self::assertSame(8, $description->rpe);

        self::assertCount(4, $description->blocks);
        self::assertSame('Tread Block 1', $description->blocks[0]->name);
        self::assertSame(14.5, $description->blocks[0]->durationMinutes);
        self::assertSame('Row Block 1', $description->blocks[3]->name);
    }
}
