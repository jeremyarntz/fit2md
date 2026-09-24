<?php

declare(strict_types=1);

namespace App\Tests\Normalization;

use App\Normalization\DescriptionNormalizerInterface;

/** Returns canned text or throws a canned exception, and counts calls. No API involved. */
final class FakeDescriptionNormalizer implements DescriptionNormalizerInterface
{
    public int $calls = 0;

    public function __construct(
        private readonly string|\Throwable $result,
    ) {
    }

    public function normalize(string $raw): string
    {
        ++$this->calls;

        if ($this->result instanceof \Throwable) {
            throw $this->result;
        }

        return $this->result;
    }
}
