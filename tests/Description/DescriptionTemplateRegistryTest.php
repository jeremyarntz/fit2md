<?php

declare(strict_types=1);

namespace App\Tests\Description;

use App\Description\DescriptionTemplateRegistry;
use App\Description\Exception\UnknownTemplateTypeException;
use App\Description\Template\OtfDescriptionTemplate;
use App\Description\Template\ResistanceDescriptionTemplate;
use App\Description\Template\RunDescriptionTemplate;
use App\Description\Text\PlainTextDescriptionParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DescriptionTemplateRegistryTest extends TestCase
{
    private DescriptionTemplateRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new DescriptionTemplateRegistry([
            new OtfDescriptionTemplate(),
            new RunDescriptionTemplate(),
            new ResistanceDescriptionTemplate(),
        ]);
    }

    public function testTypesListsEveryRegisteredTemplate(): void
    {
        self::assertSame(['otf', 'run', 'resistance'], $this->registry->types());
    }

    public function testTemplateForReturnsMatchingTemplate(): void
    {
        self::assertInstanceOf(OtfDescriptionTemplate::class, $this->registry->templateFor('otf'));
    }

    public function testTemplateForThrowsOnUnknownType(): void
    {
        $this->expectException(UnknownTemplateTypeException::class);
        $this->expectExceptionMessage('Unknown workout type "bogus". Available types: otf, run, resistance.');

        $this->registry->templateFor('bogus');
    }

    /** @param list<string> $expectedBlockNames */
    #[DataProvider('templates')]
    public function testTemplateContentParsesIntoExpectedBlankBlocks(string $type, array $expectedBlockNames): void
    {
        $content = $this->registry->templateFor($type)->content();
        $description = (new PlainTextDescriptionParser())->parseString($content);

        self::assertSame('Title', $description->title);
        self::assertNull($description->coach);
        self::assertNull($description->location);
        self::assertNull($description->rpe);
        self::assertNull($description->notes);

        self::assertSame($expectedBlockNames, array_map(
            static fn ($block) => $block->name,
            $description->blocks,
        ));

        foreach ($description->blocks as $block) {
            self::assertSame([], $block->lines);
        }
    }

    /** @return iterable<string, array{string, list<string>}> */
    public static function templates(): iterable
    {
        yield 'otf' => ['otf', ['Tread Block 1', 'Floor Block 1', 'Tread Block 2', 'Floor Block 2']];
        yield 'run' => ['run', ['Run']];
        yield 'resistance' => ['resistance', ['Warmup', 'Main', 'Accessory']];
    }
}
