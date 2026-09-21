<?php

declare(strict_types=1);

namespace App\Rendering\Twig;

use App\Domain\Description\Exercise;
use Twig\Attribute\AsTwigFilter;

final class ExerciseExtension
{
    /** "*Deadlift:* 2x8 @ 50 lb, RPE 7 (used bench)" */
    #[AsTwigFilter('exercise')]
    public function formatExercise(Exercise $exercise): string
    {
        $volume = match (true) {
            null !== $exercise->sets && null !== $exercise->reps => sprintf('%dx%d', $exercise->sets, $exercise->reps),
            null !== $exercise->reps => sprintf('%d reps', $exercise->reps),
            null !== $exercise->sets => sprintf('%d rounds', $exercise->sets),
            default => null,
        };

        $weight = null !== $exercise->weight
            ? sprintf('%s %s', $this->number($exercise->weight), $exercise->weightUnit ?? 'lb')
            : null;

        $summary = null !== $volume && null !== $weight
            ? sprintf('%s @ %s', $volume, $weight)
            : ($volume ?? $weight ?? '');

        if (null !== $exercise->rpe) {
            $summary .= sprintf(', RPE %d', $exercise->rpe);
        }

        $text = sprintf('*%s:* %s', $exercise->name, ltrim($summary, ', '));

        if (null !== $exercise->note) {
            $text .= sprintf(' (%s)', $exercise->note);
        }

        return $text;
    }

    /** 50.0 becomes "50", 22.5 stays "22.5". */
    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}