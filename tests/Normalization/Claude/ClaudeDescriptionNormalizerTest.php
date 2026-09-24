<?php

declare(strict_types=1);

namespace App\Tests\Normalization\Claude;

use App\Normalization\Claude\ClaudeDescriptionNormalizer;
use App\Normalization\Exception\MissingApiKeyException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

final class ClaudeDescriptionNormalizerTest extends TestCase
{
    public function testJoinsTextBlocksAndSkipsThinking(): void
    {
        $client = new MockHttpClient(new JsonMockResponse([
            'stop_reason' => 'end_turn',
            'content' => [
                ['type' => 'thinking', 'thinking' => ''],
                ['type' => 'text', 'text' => "Test\n\n"],
                ['type' => 'text', 'text' => "**Run**\n* 5 km easy"],
            ],
        ]), 'https://api.anthropic.com');

        self::assertSame("Test\n\n**Run**\n* 5 km easy", $this->normalizer($client)->normalize('raw'));
    }

    public function testMissingApiKeyMakesNoRequest(): void
    {
        $client = new MockHttpClient([], 'https://api.anthropic.com');

        try {
            $this->normalizer($client, apiKey: '')->normalize('raw');
            self::fail('Expected MissingApiKeyException.');
        } catch (MissingApiKeyException $e) {
            self::assertStringContainsString('.env.local', $e->getMessage());
        }

        self::assertSame(0, $client->getRequestsCount());
    }

    private function normalizer(MockHttpClient $client, string $apiKey = 'test-key'): ClaudeDescriptionNormalizer
    {
        return new ClaudeDescriptionNormalizer(
            $client,
            $apiKey,
            'claude-sonnet-5',
            1000,
            __DIR__.'/../../../templates/prompts/normalize_description.md',
        );
    }
}
