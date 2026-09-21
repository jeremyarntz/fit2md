<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Exception\WorkoutFolderException;
use App\Description\DescriptionParserInterface;
use App\Parser\ActivityParserRegistry;

final readonly class WorkoutFolderLocator
{
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg'];

    public function __construct(
        private ActivityParserRegistry $activityParsers,
        private DescriptionParserInterface $descriptionParser,
    ) {
    }

    public function locate(string $directory): WorkoutInput
    {
        if (!is_dir($directory)) {
            throw new WorkoutFolderException(sprintf('"%s" is not a folder.', $directory));
        }

        $files = glob(rtrim($directory, '/').'/*') ?: [];
        sort($files);

        $activities = [];
        $descriptions = [];
        $images = [];

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            if ($this->activityParsers->supports($file)) {
                $activities[] = $file;
            } elseif ($this->descriptionParser->supports($file)) {
                $descriptions[] = $file;
            } elseif (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true)) {
                $images[] = $file;
            }
        }

        if (1 !== count($activities)) {
            throw new WorkoutFolderException(sprintf('Expected exactly one activity file in "%s", found %d.', $directory, count($activities)));
        }

        if (count($descriptions) > 1) {
            throw new WorkoutFolderException(sprintf('Expected at most one description file in "%s", found %d.', $directory, count($descriptions)));
        }

        return new WorkoutInput($activities[0], $descriptions[0] ?? null, $images);
    }
}
