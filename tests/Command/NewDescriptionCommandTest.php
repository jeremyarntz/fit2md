<?php

declare(strict_types=1);

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;

final class NewDescriptionCommandTest extends KernelTestCase
{
    private KernelInterface $bootedKernel;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->bootedKernel = self::bootKernel();
        $this->tempDir = sys_get_temp_dir().'/fit2md-new-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $path = $this->tempDir.'/description.txt';

        if (is_file($path)) {
            unlink($path);
        }

        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testPrintsToStdoutWhenNoOutputGiven(): void
    {
        $tester = $this->tester();
        $tester->execute(['type' => 'run']);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('**Run**', $tester->getDisplay());
    }

    public function testWritesToFileAndCreatesMissingDirectory(): void
    {
        $path = $this->tempDir.'/description.txt';

        $tester = $this->tester();
        $tester->execute(['type' => 'otf', '--output' => $path]);

        $tester->assertCommandIsSuccessful();
        self::assertFileExists($path);
        self::assertStringContainsString('**Tread Block 1**', (string) file_get_contents($path));
    }

    public function testRefusesToOverwriteAnExistingFile(): void
    {
        mkdir($this->tempDir);
        $path = $this->tempDir.'/description.txt';
        file_put_contents($path, 'existing content');

        $tester = $this->tester();
        $tester->execute(['type' => 'otf', '--output' => $path]);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('already exists', $this->normalizedDisplay($tester));
        self::assertSame('existing content', file_get_contents($path));
    }

    public function testUnknownTypeFails(): void
    {
        $tester = $this->tester();
        $tester->execute(['type' => 'bogus']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Unknown workout type', $this->normalizedDisplay($tester));
    }

    private function tester(): CommandTester
    {
        $application = new Application($this->bootedKernel);
        $command = $application->find('fit2md:new');

        return new CommandTester($command);
    }

    /**
     * SymfonyStyle's error block wraps its message to the console width, which can
     * insert a newline mid-phrase. Collapse whitespace so substring assertions don't
     * depend on where that wrap happens to fall.
     */
    private function normalizedDisplay(CommandTester $tester): string
    {
        return (string) preg_replace('/\s+/', ' ', $tester->getDisplay());
    }
}
