<?php

declare(strict_types=1);

namespace App\Description\Template;

use App\Description\DescriptionTemplateInterface;

final class OtfDescriptionTemplate implements DescriptionTemplateInterface
{
    public function getType(): string
    {
        return 'otf';
    }

    public function content(): string
    {
        return <<<'TXT'
            Title
            Coach:
            Location:
            RPE:
            Notes:

            **Tread Block 1**

            **Floor Block 1**

            **Tread Block 2**

            **Floor Block 2**

            TXT;
    }
}
