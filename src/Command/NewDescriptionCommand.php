<?php

declare(strict_types=1);

namespace App\Command;

use App\Description\DescriptionTemplateRegistry;
use App\Description\Exception\UnknownTemplateTypeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'fit2md:new',
    description: 'Creates a blank workout description file',
)]
final class NewDescriptionCommand extends Command
{
    public function __construct(
        private readonly DescriptionTemplateRegistry $templates,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, sprintf('Workout type (%s)', implode(', ', $this->templates->types())))
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write the description to this file instead of stdout')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $type = $input->getArgument('type');

        if (!is_string($type)) {
            $io->error('Expected a single workout type.');

            return Command::INVALID;
        }

        try {
            $content = $this->templates->templateFor($type)->content();
        } catch (UnknownTemplateTypeException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $outputPath = $input->getOption('output');

        if (null === $outputPath) {
            $output->writeln($content);

            return Command::SUCCESS;
        }

        if (!is_string($outputPath)) {
            $io->error('Output path must be a single file path.');

            return Command::INVALID;
        }

        if (file_exists($outputPath)) {
            $io->error(sprintf('"%s" already exists. Refusing to overwrite it.', $outputPath));

            return Command::FAILURE;
        }

        $directory = dirname($outputPath);

        if (!is_dir($directory) && !mkdir($directory, recursive: true) && !is_dir($directory)) {
            $io->error(sprintf('Could not create directory "%s".', $directory));

            return Command::FAILURE;
        }

        if (false === file_put_contents($outputPath, $content)) {
            $io->error(sprintf('Could not write to "%s".', $outputPath));

            return Command::FAILURE;
        }

        $io->success(sprintf('Wrote blank %s description to %s', $type, $outputPath));

        return Command::SUCCESS;
    }
}
