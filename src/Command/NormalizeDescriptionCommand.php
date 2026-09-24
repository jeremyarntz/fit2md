<?php

declare(strict_types=1);

namespace App\Command;

use App\Application\Exception\InvalidNormalizedDescriptionException;
use App\Application\NormalizeDescription;
use App\Normalization\Exception\MissingApiKeyException;
use App\Normalization\Exception\NormalizationException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fit2md:normalize',
    description: 'Normalizes a raw workout write-up into a description file',
)]
final class NormalizeDescriptionCommand extends Command
{
    public function __construct(
        private readonly NormalizeDescription $normalizeDescription,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('input', InputArgument::REQUIRED, 'Path to the raw workout write-up (.txt or .md)')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write the description to this file instead of stdout')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite the output file if it already exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $inputPath = $input->getArgument('input');
        $outputPath = $input->getOption('output');

        if (!is_string($inputPath)) {
            $io->error('Input path must be a single file path.');

            return Command::INVALID;
        }

        if (!is_readable($inputPath)) {
            $io->error(sprintf('Input file "%s" is not readable.', $inputPath));

            return Command::INVALID;
        }

        if (null !== $outputPath && !is_string($outputPath)) {
            $io->error('Output path must be a single file path.');

            return Command::INVALID;
        }

        if (null !== $outputPath && is_file($outputPath) && !$input->getOption('force')) {
            $io->error(sprintf('Output file "%s" already exists. Use --force to overwrite.', $outputPath));

            return Command::FAILURE;
        }

        $content = file_get_contents($inputPath);

        if (false === $content) {
            $io->error(sprintf('Failed to read input file "%s".', $inputPath));

            return Command::FAILURE;
        }

        try {
            $description = $this->normalizeDescription->normalize($content);
        } catch (MissingApiKeyException|NormalizationException $e) {
            $io->getErrorStyle()->error($e->getMessage());

            return Command::FAILURE;
        } catch (InvalidNormalizedDescriptionException $e) {
            $io->getErrorStyle()->error($e->getMessage());
            $io->getErrorStyle()->writeln($e->rawOutput);

            return Command::FAILURE;
        }

        if (null === $outputPath) {
            $output->writeln($description);

            return Command::SUCCESS;
        }

        $directory = dirname($outputPath);

        if (!is_dir($directory) && !mkdir($directory, recursive: true) && !is_dir($directory)) {
            $io->error(sprintf('Could not create directory "%s".', $directory));

            return Command::FAILURE;
        }

        if (false === file_put_contents($outputPath, $description)) {
            $io->error(sprintf('Could not write to "%s".', $outputPath));

            return Command::FAILURE;
        }

        $io->success(sprintf('Wrote normalized description to %s', $outputPath));

        return Command::SUCCESS;
    }
}
