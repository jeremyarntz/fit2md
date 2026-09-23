<?php

declare(strict_types=1);

namespace App\Normalization;

use App\Normalization\Exception\MissingApiKeyException;
use App\Normalization\Exception\NormalizationException;

interface DescriptionNormalizerInterface
{
    /**
     * @throws MissingApiKeyException
     * @throws NormalizationException
     */
    public function normalize(string $raw): string;
}
