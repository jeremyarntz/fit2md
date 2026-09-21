<?php

declare(strict_types=1);

namespace App\Rendering;

use App\Domain\WorkoutSummary;
use Twig\Environment;

final readonly class MarkdownRenderer implements SummaryRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $displayTimezone = 'America/Chicago',
    ) {
    }

    public function render(WorkoutSummary $summary): string
    {
        return $this->twig->render('summary.md.twig', [
            'summary' => $summary,
            'timezone' => $this->displayTimezone,
        ]);
    }
}
