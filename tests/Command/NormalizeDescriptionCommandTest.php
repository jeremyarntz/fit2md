<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Application\NormalizeDescription;
use App\Command\NormalizeDescriptionCommand;
use App\Description\Text\PlainTextDescriptionParser;
use App\Normalization\Exception\MissingApiKeyException;
use App\Tests\Normalization\FakeDescriptionNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class NormalizeDescriptionCommandTest extends TestCase
{
    private const VALID_DESCRIPTION = "Test\n\n**Run**\n* 5 km easy";

    private string $tempDir;
    private string $inputPath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/fit2md-normalize-'.bin2hex(random_bytes(4));
        mkdir($this->tempDir);

        $this->inputPath = $this->tempDir.'/raw.txt';
        file_put_contents($this->inputPath, 'a messy write-up');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testPrintsOnlyTheDescriptionToStdout(): void
    {
        $normalizer = new FakeDescriptionNormalizer(self::VALID_DESCRIPTION);

        $tester = $this->tester($normalizer);
        $tester->execute(['input' => $this->inputPath], ['capture_stderr_separately' => true]);

        $tester->assertCommandIsSuccessful();
        self::assertSame(self::VALID_DESCRIPTION."\n", $tester->getDisplay());
        self::assertSame('', $tester->getErrorOutput());
        self::assertSame(1, $normalizer->calls);
    }

    public function testWritesOutputFileAndCreatesMissingDirectory(): void
    {
        $path = $this->tempDir.'/workout/description.txt';

        $tester = $this->tester(new FakeDescriptionNormalizer(self::VALID_DESCRIPTION));
        $tester->execute(['input' => $this->inputPath, '--output' => $path]);

        $tester->assertCommandIsSuccessful();
        self::assertSame(self::VALID_DESCRIPTION, file_get_contents($path));
    }

    public function testRefusesToOverwriteWithoutForceAndMakesNoCall(): void
    {
        $path = $this->tempDir.'/description.txt';
        file_put_contents($path, 'hand-typed notes');
        $normalizer = new FakeDescriptionNormalizer(self::VALID_DESCRIPTION);

        $tester = $this->tester($normalizer);
        $tester->execute(['input' => $this->inputPath, '--output' => $path]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('already exists', $this->collapse($tester->getDisplay()));
        self::assertSame('hand-typed notes', file_get_contents($path));
        self::assertSame(0, $normalizer->calls);
    }

    public function testForceOverwritesExistingFile(): void
    {
        $path = $this->tempDir.'/description.txt';
        file_put_contents($path, 'hand-typed notes');

        $tester = $this->tester(new FakeDescriptionNormalizer(self::VALID_DESCRIPTION));
        $tester->execute(['input' => $this->inputPath, '--output' => $path, '--force' => true]);

        $tester->assertCommandIsSuccessful();
        self::assertSame(self::VALID_DESCRIPTION, file_get_contents($path));
    }

    public function testInvalidOutputPrintsRawOutputToStderr(): void
    {
        $tester = $this->tester(new FakeDescriptionNormalizer('Great workout today!'));
        $tester->execute(['input' => $this->inputPath], ['capture_stderr_separately' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertSame('', $tester->getDisplay());
        self::assertStringContainsString('no blocks', $this->collapse($tester->getErrorOutput()));
        self::assertStringContainsString('Great workout today!', $tester->getErrorOutput());
    }

    public function testNormalizerErrorIsReportedOnStderr(): void
    {
        $normalizer = new FakeDescriptionNormalizer(
            new MissingApiKeyException('ANTHROPIC_API_KEY is not set. Add it to .env.local.'),
        );

        $tester = $this->tester($normalizer);
        $tester->execute(['input' => $this->inputPath], ['capture_stderr_separately' => true]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('.env.local', $this->collapse($tester->getErrorOutput()));
    }

    public function testMissingInputFileIsInvalid(): void
    {
        $normalizer = new FakeDescriptionNormalizer(self::VALID_DESCRIPTION);

        $tester = $this->tester($normalizer);
        $tester->execute(['input' => $this->tempDir.'/missing.txt']);

        self::assertSame(Command::INVALID, $tester->getStatusCode());
        self::assertSame(0, $normalizer->calls);
    }

    private function tester(FakeDescriptionNormalizer $normalizer): CommandTester
    {
        return new CommandTester(new NormalizeDescriptionCommand(
            new NormalizeDescription($normalizer, new PlainTextDescriptionParser()),
        ));
    }

    /** SymfonyStyle wraps error blocks to the console width; collapse whitespace so wraps don't matter. */
    private function collapse(string $text): string
    {
        return (string) preg_replace('/\s+/', ' ', $text);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $directory.'/'.$entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
