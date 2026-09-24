<?php

declare(strict_types=1);

namespace App\Normalization\Claude;

use App\Normalization\DescriptionNormalizerInterface;
use App\Normalization\Exception\MissingApiKeyException;
use App\Normalization\Exception\NormalizationException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ClaudeDescriptionNormalizer implements DescriptionNormalizerInterface
{
    public function __construct(
        #[Target('anthropic.client')]
        private HttpClientInterface $client,
        #[Autowire(env: 'ANTHROPIC_API_KEY')]
        private string $apiKey,
        #[Autowire(env: 'FIT2MD_NORMALIZER_MODEL')]
        private string $model,
        #[Autowire(env: 'int:FIT2MD_NORMALIZER_MAX_TOKENS')]
        private int $maxTokens,
        #[Autowire('%kernel.project_dir%/templates/prompts/normalize_description.md')]
        private string $promptPath,
    ) {
    }

    public function normalize(string $raw): string
    {
        if ('' === trim($this->apiKey)) {
            throw new MissingApiKeyException('ANTHROPIC_API_KEY is not set. Add it to .env.local.');
        }

        try {
            $response = $this->client->request('POST', '/v1/messages', [
                'headers' => ['x-api-key' => $this->apiKey],
                'json' => [
                    'model' => $this->model,
                    'max_tokens' => $this->maxTokens,
                    'output_config' => ['effort' => 'low'],
                    'system' => $this->prompt(),
                    'messages' => [['role' => 'user', 'content' => $raw]],
                ],
            ]);

            $status = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (TransportExceptionInterface|DecodingExceptionInterface $e) {
            throw new NormalizationException(sprintf('Claude API request failed: %s', $e->getMessage()), 0, $e);
        }

        if ($status >= 400) {
            throw new NormalizationException(sprintf('Claude API returned HTTP %d: %s', $status, $this->errorMessage($data)));
        }

        $stopReason = $data['stop_reason'] ?? null;

        if ('max_tokens' === $stopReason) {
            throw new NormalizationException('The response was cut off. Raise FIT2MD_NORMALIZER_MAX_TOKENS.');
        }

        if ('refusal' === $stopReason) {
            throw new NormalizationException('The model declined to process this write-up.');
        }

        $text = $this->stripCodeFences($this->text($data));

        if ('' === $text) {
            throw new NormalizationException('The model returned an empty response.');
        }

        return $text;
    }

    private function prompt(): string
    {
        $prompt = is_readable($this->promptPath) ? file_get_contents($this->promptPath) : false;

        if (false === $prompt) {
            throw new NormalizationException(sprintf('Cannot read prompt file "%s".', $this->promptPath));
        }

        return $prompt;
    }

    /**
     * Joins every text block. Thinking blocks come first on some models, so content[0] isn't safe.
     *
     * @param array<mixed> $data
     */
    private function text(array $data): string
    {
        $content = $data['content'] ?? null;
        $text = '';

        foreach (is_array($content) ? $content : [] as $block) {
            if (is_array($block) && 'text' === ($block['type'] ?? null) && is_string($block['text'] ?? null)) {
                $text .= $block['text'];
            }
        }

        return $text;
    }

    /** The prompt forbids code fences, but a model may add them anyway. */
    private function stripCodeFences(string $text): string
    {
        $text = trim($text);

        if (1 === preg_match('/^```[a-z]*\R(?<body>.*)\R```$/s', $text, $match)) {
            return trim($match['body']);
        }

        return $text;
    }

    /** @param array<mixed> $data */
    private function errorMessage(array $data): string
    {
        $error = $data['error'] ?? null;

        return is_array($error) && is_string($error['message'] ?? null) ? $error['message'] : 'unknown error';
    }
}
