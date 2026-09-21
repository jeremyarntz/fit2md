<?php

declare(strict_types=1);

namespace App\Domain\Description;

final readonly class Block
{
    /**
     * @param list<BlockLine> $lines
     */
    public function __construct(
        public string $name,
        public array $lines,
        public ?float $durationMinutes = null,
    ) {
    }
}
