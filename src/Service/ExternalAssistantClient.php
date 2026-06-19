<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Service;

use Maoxtrem\AsistenteIa\DTO\ChatRequest;
use Maoxtrem\AsistenteIa\DTO\ChatResponse;
use Maoxtrem\AsistenteIa\DTO\ConversationBootstrapRequest;
use Maoxtrem\AsistenteIa\DTO\ConversationBootstrapResponse;
use Maoxtrem\AsistenteIa\DTO\FeedbackRequest;
use Maoxtrem\AsistenteIa\DTO\FeedbackResponse;
use Throwable;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExternalAssistantClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $bootstrapEndpoint,
        private readonly string $chatEndpoint,
        private readonly string $feedbackEndpoint,
        private readonly string $apiKey,
        private readonly float $connectTimeout,
        private readonly float $timeout,
        private readonly bool $verifyPeer,
        private readonly bool $verifyHost,
        private readonly array $defaultHeaders = [],
    ) {
    }

    public function sendMessage(ChatRequest $chatRequest): ChatResponse
    {
        try {
            $response = $this->httpClient->request('POST', $this->buildUrl(), [
                'headers' => $this->buildHeaders(),
                'json' => $chatRequest->toArray(),
                'max_connect_duration' => $this->connectTimeout,
                'timeout' => $this->timeout,
                'verify_peer' => $this->verifyPeer,
                'verify_host' => $this->verifyHost,
            ]);

            $payload = $response->toArray(false);
        } catch (Throwable $exception) {
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

    public function bootstrapConversation(ConversationBootstrapRequest $bootstrapRequest): ConversationBootstrapResponse
    {
        try {
            $response = $this->httpClient->request('POST', $this->buildBootstrapUrl(), [
                'headers' => $this->buildHeaders(),
                'json' => $bootstrapRequest->toArray(),
                'max_connect_duration' => $this->connectTimeout,
                'timeout' => $this->timeout,
                'verify_peer' => $this->verifyPeer,
                'verify_host' => $this->verifyHost,
            ]);

            $payload = $response->toArray(false);
        } catch (Throwable $exception) {
            return new ConversationBootstrapResponse(
                conversationId: null,
                messages: [],
                raw: ['error' => $exception->getMessage()],
            );
        }

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $conversationId = $data['conversation_id'] ?? $payload['conversation_id'] ?? null;
        $messages = is_array($data['messages'] ?? null) ? $data['messages'] : [];

        return new ConversationBootstrapResponse(
            conversationId: is_string($conversationId) && trim($conversationId) !== '' ? $conversationId : null,
            messages: $messages,
            raw: is_array($payload) ? $payload : [],
        );
    }

    public function sendFeedback(FeedbackRequest $feedbackRequest): FeedbackResponse
    {
        try {
            $response = $this->httpClient->request('POST', $this->buildFeedbackUrl(), [
                'headers' => $this->buildHeaders(),
                'json' => $feedbackRequest->toArray(),
                'max_connect_duration' => $this->connectTimeout,
                'timeout' => $this->timeout,
                'verify_peer' => $this->verifyPeer,
                'verify_host' => $this->verifyHost,
            ]);

            $payload = $response->toArray(false);
        } catch (Throwable $exception) {
            return new FeedbackResponse(
                ok: false,
                message: 'No fue posible conectar con el microservicio de feedback.',
                raw: ['error' => $exception->getMessage()],
            );
        }

        $message = trim((string) ($payload['data']['message'] ?? $payload['message'] ?? ''));

        return new FeedbackResponse(
            ok: true,
            message: $message !== '' ? $message : 'El microservicio no devolvio una respuesta de feedback.',
            raw: is_array($payload) ? $payload : [],
        );
    }

    private function buildUrl(): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($this->chatEndpoint, '/');
    }

    private function buildBootstrapUrl(): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($this->bootstrapEndpoint, '/');
    }

    private function buildFeedbackUrl(): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($this->feedbackEndpoint, '/');
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
