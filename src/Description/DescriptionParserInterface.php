<?php

declare(strict_types=1);

namespace App\Description;

use App\Description\Exception\DescriptionParseException;
use App\Domain\Description\WorkoutDescription;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.description_parser')]
interface DescriptionParserInterface
{
    public function supports(string $path): bool;

    /** @throws DescriptionParseException */
    public function parse(string $path): WorkoutDescription;
}
