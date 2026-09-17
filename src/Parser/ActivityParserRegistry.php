<?php

declare(strict_types=1);

namespace App\Parser;

use App\Parser\Exception\UnsupportedFileException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ActivityParserRegistry
{
    /** @param iterable<ActivityParserInterface> $parsers */
    public function __construct(
        #[AutowireIterator('app.activity_parser')]
        private iterable $parsers,
    ) {
    }

    /** @throws UnsupportedFileException */
    public function parserFor(string $path): ActivityParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($path)) {
                return $parser;
            }
        }

        throw new UnsupportedFileException(sprintf('No parser supports "%s".', $path));
    }
}
