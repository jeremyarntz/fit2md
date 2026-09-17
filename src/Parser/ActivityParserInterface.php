<?php

declare(strict_types=1);

namespace App\Parser;

use App\Domain\Activity;
use App\Parser\Exception\ActivityParseException;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.activity_parser')]
interface ActivityParserInterface
{
    public function supports(string $path): bool;

    /** @throws ActivityParseException */
    public function parse(string $path): Activity;
}
