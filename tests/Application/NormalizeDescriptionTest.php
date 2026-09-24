<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\Exception\InvalidNormalizedDescriptionException;
use App\Application\NormalizeDescription;
use App\Description\Text\PlainTextDescriptionParser;
use App\Normalization\DescriptionNormalizerInterface;
use App\Normalization\Exception\NormalizationException;
use PHPUnit\Framework\TestCase;

final class NormalizeDescriptionTest extends TestCase
{
    public function testReturnsValidOutputUnchanged(): void
    {
        $text = "Test\n\n**Run**\n* 5 km easy";

        self::assertSame($text, $this->service($text)->normalize('raw'));
    }

    public function testRejectsOutputWithoutBlocksAndKeepsRawOutput(): void
    {
        try {
            $this->service('Great workout today!')->normalize('raw');
            self::fail('Expected InvalidNormalizedDescriptionException.');
        } catch (InvalidNormalizedDescriptionException $e) {
            self::assertSame('Great workout today!', $e->rawOutput);
        }
    }

    public function testNormalizerErrorsPassThrough(): void
    {
        $failing = new class implements DescriptionNormalizerInterface {
            public function normalize(string $raw): string
            {
                throw new NormalizationException('API down');
            }
        };

        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage('API down');

        (new NormalizeDescription($failing, new PlainTextDescriptionParser()))->normalize('raw');
    }

    /** A normalizer that returns canned text, so no API is involved. */
    private function service(string $modelOutput): NormalizeDescription
    {
        $fake = new class($modelOutput) implements DescriptionNormalizerInterface {
            public function __construct(private readonly string $output)
            {
            }

            public function normalize(string $raw): string
            {
                return $this->output;
            }
        };

        return new NormalizeDescription($fake, new PlainTextDescriptionParser());
    }
}
