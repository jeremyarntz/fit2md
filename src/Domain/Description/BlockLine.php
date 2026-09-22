<?php

declare(strict_types=1);

namespace App\Domain\Description;

final readonly class BlockLine
{
    public function __construct(
        public string $text,
        public ?Exercise $exercise = null,
    ) {
    }
}
