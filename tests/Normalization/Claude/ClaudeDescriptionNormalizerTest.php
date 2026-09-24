<?php

declare(strict_types=1);

namespace App\Tests\Normalization\Claude;

use App\Normalization\Claude\ClaudeDescriptionNormalizer;
use App\Normalization\Exception\MissingApiKeyException;
use App\Normalization\Exception\NormalizationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ClaudeDescriptionNormalizerTest extends TestCase
{
    private const BASE_URI = 'https://api.anthropic.com';
    private const PROMPT_PATH = __DIR__.'/../../../templates/prompts/normalize_description.md';

    public function testJoinsTextBlocksAndSkipsThinking(): void
    {
        $client = new MockHttpClient(new JsonMockResponse([
            'stop_reason' => 'end_turn',
            'content' => [
                ['type' => 'thinking', 'thinking' => ''],
                ['type' => 'text', 'text' => "Test\n\n"],
                ['type' => 'text', 'text' => "**Run**\n* 5 km easy"],
            ],
        ]), self::BASE_URI);

        self::assertSame("Test\n\n**Run**\n* 5 km easy", $this->normalizer($client)->normalize('raw'));
    }

    public function testMissingApiKeyMakesNoRequest(): void
    {
        $client = new MockHttpClient([], self::BASE_URI);

        try {
            $this->normalizer($client, apiKey: '')->normalize('raw');
            self::fail('Expected MissingApiKeyException.');
        } catch (MissingApiKeyException $e) {
            self::assertStringContainsString('.env.local', $e->getMessage());
        }

        self::assertSame(0, $client->getRequestsCount());
    }

    public function testSendsModelPromptAndRawWriteUp(): void
    {
        $response = $this->textResponse("Test\n\n**Run**");
        $this->normalizer(new MockHttpClient($response, self::BASE_URI))->normalize('my raw write-up');

        $options = $response->getRequestOptions();
        self::assertIsArray($options['headers']);
        self::assertIsString($options['body']);

        self::assertSame('POST', $response->getRequestMethod());
        self::assertSame('https://api.anthropic.com/v1/messages', $response->getRequestUrl());
        self::assertContains('x-api-key: test-key', $options['headers']);
        self::assertSame([
            'model' => 'claude-sonnet-5',
            'max_tokens' => 1000,
            'output_config' => ['effort' => 'low'],
            'system' => file_get_contents(self::PROMPT_PATH),
            'messages' => [['role' => 'user', 'content' => 'my raw write-up']],
        ], json_decode($options['body'], true));
    }

    public function testStripsCodeFences(): void
    {
        $client = new MockHttpClient($this->textResponse("```markdown\nTest\n\n**Run**\n```"), self::BASE_URI);

        self::assertSame("Test\n\n**Run**", $this->normalizer($client)->normalize('raw'));
    }

    public function testApiErrorIncludesTheApiMessage(): void
    {
        $client = new MockHttpClient(new JsonMockResponse(
            ['type' => 'error', 'error' => ['type' => 'invalid_request_error', 'message' => 'model: not found']],
            ['http_code' => 400],
        ), self::BASE_URI);

        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage('HTTP 400: model: not found');

        $this->normalizer($client)->normalize('raw');
    }

    #[DataProvider('unusableStopReasons')]
    public function testRejectsUnusableStopReason(string $stopReason, string $expectedMessage): void
    {
        $client = new MockHttpClient($this->textResponse('partial', $stopReason), self::BASE_URI);

        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->normalizer($client)->normalize('raw');
    }

    /** @return iterable<string, array{string, string}> */
    public static function unusableStopReasons(): iterable
    {
        yield 'cut off' => ['max_tokens', 'FIT2MD_NORMALIZER_MAX_TOKENS'];
        yield 'refused' => ['refusal', 'declined'];
    }

    public function testEmptyResponseFails(): void
    {
        $client = new MockHttpClient(new JsonMockResponse(['stop_reason' => 'end_turn', 'content' => []]), self::BASE_URI);

        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage('empty response');

        $this->normalizer($client)->normalize('raw');
    }

    public function testNetworkErrorIsWrapped(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['error' => 'Connection refused']), self::BASE_URI);

        $this->expectException(NormalizationException::class);
        $this->expectExceptionMessage('Connection refused');

        $this->normalizer($client)->normalize('raw');
    }

    private function normalizer(MockHttpClient $client, string $apiKey = 'test-key'): ClaudeDescriptionNormalizer
    {
        return new ClaudeDescriptionNormalizer(
            $client,
            $apiKey,
            'claude-sonnet-5',
            1000,
            self::PROMPT_PATH,
        );
    }

    /** A successful API response containing one text block. */
    private function textResponse(string $text, string $stopReason = 'end_turn'): JsonMockResponse
    {
        return new JsonMockResponse([
            'stop_reason' => $stopReason,
            'content' => [['type' => 'text', 'text' => $text]],
        ]);
    }
}
