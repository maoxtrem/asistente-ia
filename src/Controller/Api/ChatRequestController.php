<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Controller\Api;

use Maoxtrem\AsistenteIa\DTO\ChatRequest;
use Maoxtrem\AsistenteIa\Service\ExternalAssistantClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ChatRequestController
{
    public function __construct(
        private readonly ExternalAssistantClient $assistantClient,
        private readonly string $tenantName,
    ) {
    }

    #[Route('/api/v1/asistente-ia/message', name: 'asistente_ia_message', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return new JsonResponse([
                'ok' => false,
                'error' => [
                    'message' => 'Invalid JSON payload.',
                    'code' => 'invalid_json',
                ],
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $payload['tenant'] = trim((string) ($payload['tenant'] ?? $this->tenantName));
        $chatRequest = ChatRequest::fromArray($payload);

        if ($chatRequest->message === '') {
            return new JsonResponse([
                'ok' => false,
                'error' => [
                    'message' => 'The message field is required.',
                    'code' => 'message_required',
                ],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $response = $this->assistantClient->sendMessage($chatRequest);
        $payload = $response->toArray();
        $rawPayload = is_array($payload['raw'] ?? null) ? $payload['raw'] : [];
        $links = $rawPayload['data']['links'] ?? $rawPayload['links'] ?? [];

        return new JsonResponse([
            'ok' => true,
            'data' => [
                'message' => $payload['message'],
                'conversation_id' => $payload['conversation_id'],
                'links' => is_array($links) ? $links : [],
                'bundle' => [
                    'widget_url' => '/asistente-ia/widget',
                    'vector_form_url' => '/asistente-ia/vectorial',
                ],
                'raw' => $rawPayload,
            ],
        ]);
    }
}
