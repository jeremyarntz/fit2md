<?php

declare(strict_types=1);

namespace App\Command;

use App\Parser\ActivityParserRegistry;
use App\Parser\Exception\ActivityParseException;
use App\Parser\Exception\UnsupportedFileException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fit2md:inspect',
    description: 'Parses a .fit file',
)]
final class InspectActivityCommand extends Command
{
    // Inject services here if needed via dependency injection
    public function __construct(private readonly ActivityParserRegistry $registry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('workoutDataPath', InputArgument::REQUIRED, 'workout data path')
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
            $parser = $this->registry->parserFor($workoutDataPath);
            $activity = $parser->parse($workoutDataPath);
        } catch (UnsupportedFileException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        } catch (ActivityParseException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->title('Activity summary');

        $io->definitionList(
            ['Started (UTC)' => $activity->startedAt->format('Y-m-d H:i:s')],
            ['Duration' => sprintf('%d s', $activity->durationSeconds())],
            ['Samples' => count($activity->samples)],
            ['Sport' => $activity->sport ?? '—'],
        );

        $rows = [];

        foreach ($activity->laps as $lap) {
            $rows[] = [
                $lap->index,
                sprintf('%.1f', $lap->timerSeconds),
                $lap->avgHeartRate ?? '—',
                $lap->maxHeartRate ?? '—',
            ];
        }

        $io->table(['#', 'Timer (s)', 'Avg HR', 'Max HR'], $rows);

        return Command::SUCCESS;
    }
}
