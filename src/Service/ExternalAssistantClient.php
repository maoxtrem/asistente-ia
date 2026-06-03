<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Service;

use Maoxtrem\AsistenteIa\DTO\ChatRequest;
use Maoxtrem\AsistenteIa\DTO\ChatResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExternalAssistantClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $chatEndpoint,
        private readonly string $apiKey,
        private readonly float $connectTimeout,
        private readonly float $timeout,
        private readonly array $defaultHeaders = [],
    ) {
    }

    public function sendMessage(ChatRequest $chatRequest): ChatResponse
    {
        try {
            $response = $this->httpClient->request('POST', $this->buildUrl(), [
                'headers' => $this->buildHeaders(),
                'json' => $chatRequest->toArray(),
                'connect_timeout' => $this->connectTimeout,
                'timeout' => $this->timeout,
            ]);

            $payload = $response->toArray(false);
        } catch (TransportExceptionInterface $exception) {
            return new ChatResponse(
                message: 'No fue posible conectar con el microservicio del asistente.',
                raw: ['error' => $exception->getMessage()],
            );
        }

        $message = trim((string) ($payload['data']['message'] ?? $payload['message'] ?? ''));
        $conversationId = $payload['data']['conversation_id'] ?? $payload['conversation_id'] ?? null;

        return new ChatResponse(
            message: $message !== '' ? $message : 'El microservicio no devolvio un mensaje.',
            conversationId: is_string($conversationId) && trim($conversationId) !== '' ? $conversationId : null,
            raw: is_array($payload) ? $payload : [],
        );
    }

    private function buildUrl(): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($this->chatEndpoint, '/');
    }

    private function buildHeaders(): array
    {
        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $this->defaultHeaders);

        if ($this->apiKey !== '') {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        return $headers;
    }
}
