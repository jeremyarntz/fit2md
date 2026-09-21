<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Zone
{
    public function __construct(
        public string $name,
        public ?int $maxBpm = null,
    ) {
    }
}
