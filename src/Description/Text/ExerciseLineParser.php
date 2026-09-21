<?php

declare(strict_types=1);

namespace App\Description\Text;

use App\Domain\Description\Exercise;

final class ExerciseLineParser
{
    private const WEIGHT = '(?<weight>\d+(?:\.\d+)?)\s*(?<unit>#|lbs?|kg)';

    /** "Deadlift 2x8 @ 50 lb RPE 7" */
    private const SETS_REPS_PATTERN = '/^(?<name>.+?)\s+(?<sets>\d+)\s*x\s*(?<reps>\d+)(?:\s*@\s*'.self::WEIGHT.')?(?:\s+rpe\s*(?<rpe>\d{1,2}))?$/i';

    /** "8 x deadlift (slow)" */
    private const LEADING_REPS_PATTERN = '/^(?<reps>\d+)\s*x\s+(?<name>.+)$/i';

    private const WEIGHT_PATTERN = '/^'.self::WEIGHT.'$/i';
    private const ROUNDS_PATTERN = '/^(?<rounds>\d+)\s*rounds?$/i';
    private const RPE_PATTERN = '/^rpe\s*(?<rpe>\d{1,2})$/i';

    /** Returns null when the line isn't an exercise, so it stays plain text. */
    public function parse(string $line): ?Exercise
    {
        $pieces = explode('|', $line);
        $head = trim($pieces[0]);

        $name = $head;
        $sets = null;
        $reps = null;
        $weight = null;
        $unit = null;
        $rpe = null;
        $notes = [];

        if (1 === preg_match(self::SETS_REPS_PATTERN, $head, $match, PREG_UNMATCHED_AS_NULL)) {
            $name = $match['name'];
            $sets = (int) $match['sets'];
            $reps = (int) $match['reps'];

            if (null !== $match['weight'] && null !== $match['unit']) {
                $weight = (float) $match['weight'];
                $unit = $this->unit($match['unit']);
            }

            if (null !== $match['rpe']) {
                $rpe = (int) $match['rpe'];
            }
        } elseif (1 === preg_match(self::LEADING_REPS_PATTERN, $head, $match)) {
            $name = $match['name'];
            $reps = (int) $match['reps'];
        }

        foreach (array_slice($pieces, 1) as $rawPiece) {
            $piece = trim($rawPiece);

            if ('' === $piece) {
                continue;
            }

            if (1 === preg_match(self::WEIGHT_PATTERN, $piece, $match)) {
                $weight = (float) $match['weight'];
                $unit = $this->unit($match['unit']);
            } elseif (1 === preg_match(self::ROUNDS_PATTERN, $piece, $match)) {
                $sets = (int) $match['rounds'];
            } elseif (1 === preg_match(self::RPE_PATTERN, $piece, $match)) {
                $rpe = (int) $match['rpe'];
            } else {
                $notes[] = $piece;
            }
        }

        if (null === $sets && null === $reps && null === $weight && null === $rpe) {
            return null;
        }

        return new Exercise(
            name: trim($name),
            sets: $sets,
            reps: $reps,
            weight: $weight,
            weightUnit: $unit,
            rpe: $rpe,
            note: [] === $notes ? null : implode('; ', $notes),
        );
    }

    private function unit(string $raw): string
    {
        return 'kg' === strtolower($raw) ? 'kg' : 'lb';
    }
}