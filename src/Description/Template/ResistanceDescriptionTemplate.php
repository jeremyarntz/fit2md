<?php

declare(strict_types=1);

namespace App\Description\Template;

use App\Description\DescriptionTemplateInterface;

final class ResistanceDescriptionTemplate implements DescriptionTemplateInterface
{
    public function getType(): string
    {
        return 'resistance';
    }

    public function content(): string
    {
        return <<<'TXT'
            Title
            Coach:
            Location:
            RPE:
            Notes:

            **Warmup**

            **Main**

            **Accessory**

            TXT;
    }
}
