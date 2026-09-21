<?php

declare(strict_types=1);

namespace App\Tests\Rendering;

use App\Application\SummarizeWorkout;
use App\Application\WorkoutInput;
use App\Rendering\SummaryRendererInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SummaryRenderingTest extends KernelTestCase
{
    public function testRendersExpectedSummary(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $summarizer = $container->get(SummarizeWorkout::class);
        $renderer = $container->get(SummaryRendererInterface::class);

        self::assertInstanceOf(SummarizeWorkout::class, $summarizer);
        self::assertInstanceOf(SummaryRendererInterface::class, $renderer);

        $fixtures = __DIR__.'/../Fixtures';

        $summary = $summarizer->summarize(new WorkoutInput(
            $fixtures.'/tread_50.fit',
            $fixtures.'/hyrox_p1w2.txt',
        ));

        self::assertStringEqualsFile(
            $fixtures.'/tread_50_hyrox.expected.md',
            $renderer->render($summary),
        );
    }
}
