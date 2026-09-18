<?php

declare(strict_types=1);

namespace App\Rendering;

use App\Domain\Activity;
use App\Domain\HeartRateStats;

final readonly class MarkdownRenderer implements SummaryRendererInterface
{
    public function __construct(
        private readonly string $displayTimezone = 'America/Chicago',
    ) {
    }

    public function render(Activity $activity): string
    {
        $local = $activity->startedAt->setTimezone(new \DateTimeZone($this->displayTimezone));
        $workoutStats = HeartRateStats::fromSamples($activity->samples);
        $lapCount = count($activity->laps);

        $lines = [];
        $lines[] = '# Workout Summary';
        $lines[] = '';
        $lines[] = sprintf('Date: %s', $local->format('Y-m-d H:i'));
        $lines[] = sprintf('Duration: %s', $this->formatDuration($activity->durationSeconds()));
        $lines[] = sprintf('Laps: %s', $lapCount);
        $lines[] = sprintf('Avg HR: %s', $workoutStats->average);
        $lines[] = sprintf('Min HR: %s', $workoutStats->min);
        $lines[] = sprintf('Max HR: %s', $workoutStats->max);

        $lines[] = '## Laps Summary';
        $lines[] = '| lap num | duration | avg hr | min hr | max hr |';
        $lines[] = '|---|---|---|---|---|';
        foreach ($activity->laps as $lap) {
            $samples = $activity->samplesBetween($lap->startTime, $lap->endTime);
            $stats = HeartRateStats::fromSamples($samples);

            $lines[] = sprintf(
                '| %d | %s | %s | %s | %s |',
                $lap->index + 1,
                $this->formatDuration((int) $lap->timerSeconds),
                $stats->average ?? '—',
                $stats->min ?? '—',
                $stats->max ?? '—',
            );
        }

        return implode("\n", $lines)."\n";
    }

    private function formatDuration(int $seconds): string
    {
        return gmdate('i:s', $seconds);
    }
}
