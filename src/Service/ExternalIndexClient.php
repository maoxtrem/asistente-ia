<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Service;

use Maoxtrem\AsistenteIa\Contract\IndexableDocumentInterface;
use Maoxtrem\AsistenteIa\DTO\IndexDocument;
use Maoxtrem\AsistenteIa\DTO\IndexDocumentResponse;
use Throwable;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExternalIndexClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $indexEndpoint,
        private readonly string $apiKey,
        private readonly float $connectTimeout,
        private readonly float $timeout,
        private readonly bool $verifyPeer,
        private readonly bool $verifyHost,
        private readonly array $defaultHeaders = [],
    ) {
    }

    /**
     * @param array<string, mixed> $extraMetadata
     */
    public function indexIndexable(IndexableDocumentInterface $document, string $operation = 'upsert', array $extraMetadata = []): IndexDocumentResponse
    {
        return $this->index(IndexDocument::fromIndexable($document, $operation, $extraMetadata));
    }

    public function index(IndexDocument $document): IndexDocumentResponse
    {
        try {
            $response = $this->httpClient->request('POST', $this->buildUrl(), [
                'headers' => $this->buildHeaders(),
                'json' => $document->toArray(),
                'max_connect_duration' => $this->connectTimeout,
                'timeout' => $this->timeout,
                'verify_peer' => $this->verifyPeer,
                'verify_host' => $this->verifyHost,
            ]);

            $payload = $response->toArray(false);
        } catch (Throwable $exception) {
            return new IndexDocumentResponse(
                ok: false,
                message: 'No fue posible conectar con el microservicio de indexacion.',
                raw: ['error' => $exception->getMessage()],
            );
        }

        $message = trim((string) ($payload['data']['message'] ?? $payload['message'] ?? ''));
        $collection = $payload['data']['collection'] ?? $payload['collection'] ?? null;
        $pointId = $payload['data']['point_id'] ?? $payload['point_id'] ?? null;

        return new IndexDocumentResponse(
            ok: true,
            message: $message !== '' ? $message : 'El microservicio no devolvio un mensaje de indexacion.',
            collection: is_string($collection) && trim($collection) !== '' ? $collection : null,
            pointId: is_string($pointId) && trim($pointId) !== '' ? $pointId : null,
            raw: is_array($payload) ? $payload : [],
        );
    }

    private function buildUrl(): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($this->indexEndpoint, '/');
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
