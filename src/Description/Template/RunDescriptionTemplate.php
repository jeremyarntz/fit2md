<?php

declare(strict_types=1);

namespace App\Description\Template;

use App\Description\DescriptionTemplateInterface;

final class RunDescriptionTemplate implements DescriptionTemplateInterface
{
    public function getType(): string
    {
        return 'run';
    }

    public function content(): string
    {
        return <<<'TXT'
            Title
            Location:
            RPE:
            Notes:

            **Run**

            TXT;
    }
}
