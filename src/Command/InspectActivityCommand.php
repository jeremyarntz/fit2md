<?php

declare(strict_types=1);

namespace App\Command;

use App\Application\Exception\WorkoutFolderException;
use App\Application\SummarizeWorkout;
use App\Application\WorkoutFolderLocator;
use App\Application\WorkoutInput;
use App\Description\Exception\DescriptionParseException;
use App\Parser\Exception\ActivityParseException;
use App\Parser\Exception\UnsupportedFileException;
use App\Rendering\SummaryRendererInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fit2md:inspect',
    description: 'Parses a .fit file',
)]
final class InspectActivityCommand extends Command
{
    public function __construct(
        private readonly SummarizeWorkout $summarizer,
        private readonly SummaryRendererInterface $renderer,
        private readonly WorkoutFolderLocator $locator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('workoutDataPath', InputArgument::REQUIRED, 'workout data path')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write the summary to this file instead of stdout')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $workoutDataPath = $input->getArgument('workoutDataPath');

        if (!is_string($workoutDataPath)) {
            $io->error('Expected a single file path.');

            return Command::INVALID;
        }

        try {
            $workoutInput = is_dir($workoutDataPath)
                ? $this->locator->locate($workoutDataPath)
                : new WorkoutInput($workoutDataPath);

            $summary = $this->summarizer->summarize($workoutInput);
            $markdown = $this->renderer->render($summary);
        } catch (UnsupportedFileException|ActivityParseException|DescriptionParseException|WorkoutFolderException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $outputPath = $input->getOption('output');

        if (null === $outputPath) {
            $output->writeln($markdown);

            return Command::SUCCESS;
        }

        if (!is_string($outputPath)) {
            $io->error('Output path must be a single file path.');

            return Command::INVALID;
        }

        if (false === file_put_contents($outputPath, $markdown)) {
            $io->error(sprintf('Could not write to "%s".', $outputPath));

            return Command::FAILURE;
        }

        $io->success(sprintf('Wrote summary to %s', $outputPath));

        return Command::SUCCESS;
    }
}
