<?php

declare(strict_types=1);

namespace App\Rendering;

use App\Domain\Activity;

interface SummaryRendererInterface
{
    public function render(Activity $activity): string;
}
