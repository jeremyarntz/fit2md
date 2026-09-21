<?php

declare(strict_types=1);

namespace App\Rendering\Twig;

use Twig\Attribute\AsTwigFilter;

final class DurationExtension
{
    #[AsTwigFilter('duration')]
    public function formatDuration(int|float $seconds): string
    {
        $seconds = (int) round($seconds);

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
