<?php

declare(strict_types=1);

namespace App\Description\Text;

use App\Description\DescriptionParserInterface;
use App\Description\Exception\DescriptionParseException;
use App\Domain\Description\Block;
use App\Domain\Description\WorkoutDescription;

final class PlainTextDescriptionParser implements DescriptionParserInterface
{
    /** Matches "**Tread Block 1 (14.5 min)**" or "**Floor**" */
    private const HEADER_PATTERN = '/^\*\*(?<name>.+?)(?:\s*\((?<minutes>\d+(?:\.\d+)?)\s*min\))?\*\*$/i';

    /** Matches "Coach: Natalie" */
    private const FIELD_PATTERN = '/^(?<key>[a-z]+):\s*(?<value>.+)$/i';

    private const KNOWN_FIELDS = ['coach', 'location', 'rpe', 'notes'];

    public function supports(string $path): bool
    {
        return 'txt' === strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    public function parse(string $path): WorkoutDescription
    {
        if (!is_readable($path)) {
            throw new DescriptionParseException(sprintf('Cannot read file "%s".', $path));
        }

        $contents = file_get_contents($path);

        if (false === $contents) {
            throw new DescriptionParseException(sprintf('Failed to read file "%s".', $path));
        }

        return $this->parseString($contents);
    }

    public function parseString(string $contents): WorkoutDescription
    {
        $title = null;
        $fields = [];
        $blocks = [];

        $blockName = null;
        $blockMinutes = null;
        $blockLines = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $rawLine) {
            $line = trim($rawLine);

            if ('' === $line) {
                continue;
            }

            // A bold line starts a new block.
            if (1 === preg_match(self::HEADER_PATTERN, $line, $match)) {
                if (null !== $blockName) {
                    $blocks[] = new Block($blockName, $blockLines, $blockMinutes);
                }

                $blockName = trim($match['name']);
                $minutes = $match['minutes'] ?? '';
                $blockMinutes = '' !== $minutes ? (float) $minutes : null;
                $blockLines = [];

                continue;
            }

            // Inside a block, every line belongs to it.
            if (null !== $blockName) {
                $blockLines[] = $line;

                continue;
            }

            // Before the first block: known "Key: value" fields, otherwise the title.
            if (1 === preg_match(self::FIELD_PATTERN, $line, $match)
                && in_array(strtolower($match['key']), self::KNOWN_FIELDS, true)) {
                $fields[strtolower($match['key'])] = trim($match['value']);

                continue;
            }

            $title ??= $line;
        }

        // The last block has no header after it, so save it here.
        if (null !== $blockName) {
            $blocks[] = new Block($blockName, $blockLines, $blockMinutes);
        }

        return new WorkoutDescription(
            title: $title,
            coach: $fields['coach'] ?? null,
            location: $fields['location'] ?? null,
            rpe: $this->parseRpe($fields['rpe'] ?? null),
            notes: $fields['notes'] ?? null,
            blocks: $blocks,
        );
    }

    /** Accepts "8" or "8/10"; anything outside 1–10 is ignored. */
    private function parseRpe(?string $value): ?int
    {
        if (null === $value || 1 !== preg_match('/^(\d{1,2})/', $value, $match)) {
            return null;
        }

        $rpe = (int) $match[1];

        return $rpe >= 1 && $rpe <= 10 ? $rpe : null;
    }
}
