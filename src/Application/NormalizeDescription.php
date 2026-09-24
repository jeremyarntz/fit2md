<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Exception\InvalidNormalizedDescriptionException;
use App\Description\Text\PlainTextDescriptionParser;
use App\Normalization\DescriptionNormalizerInterface;
use App\Normalization\Exception\MissingApiKeyException;
use App\Normalization\Exception\NormalizationException;

final readonly class NormalizeDescription
{
    public function __construct(
        private DescriptionNormalizerInterface $normalizer,
        private PlainTextDescriptionParser $parser,
    ) {
    }

    /**
     * Returns description text that is known to parse into at least one block.
     *
     * @throws MissingApiKeyException
     * @throws NormalizationException
     * @throws InvalidNormalizedDescriptionException
     */
    public function normalize(string $raw): string
    {
        $text = $this->normalizer->normalize($raw);

        // The parser accepts any text, so zero blocks is how prose shows up.
        if ([] === $this->parser->parseString($text)->blocks) {
            throw new InvalidNormalizedDescriptionException('The model output contains no blocks.', $text);
        }

        return $text;
    }
}
