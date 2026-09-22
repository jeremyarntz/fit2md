<?php

declare(strict_types=1);

namespace App\Description;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.description_template')]
interface DescriptionTemplateInterface
{
    /** A short, stable identifier used on the command line, e.g. "otf". */
    public function getType(): string;

    /** The blank description.txt content for this workout type. */
    public function content(): string;
}
