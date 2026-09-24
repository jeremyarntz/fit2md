<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class InvalidNormalizedDescriptionException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $rawOutput,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
