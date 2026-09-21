<?php

declare(strict_types=1);

namespace App\Rendering;

use App\Domain\WorkoutSummary;

interface SummaryRendererInterface
{
    public function render(WorkoutSummary $summary): string;
}
