<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Controller\Api;

use Maoxtrem\AsistenteIa\DTO\ConversationBootstrapRequest;
use Maoxtrem\AsistenteIa\Service\ExternalAssistantClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class BootstrapController
{
    public function __construct(
        private readonly ExternalAssistantClient $assistantClient,
        private readonly string $tenantName,
    ) {
    }

    #[Route('/api/v1/asistente-ia/bootstrap', name: 'asistente_ia_bootstrap', methods: ['POST'])]
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

        $tenant = trim((string) ($payload['tenant'] ?? $this->tenantName));
        $clientKey = trim((string) ($payload['client_key'] ?? ''));

        if ($clientKey === '') {
            return new JsonResponse([
                'ok' => false,
                'error' => [
                    'message' => 'client_key is required.',
                    'code' => 'client_key_required',
                ],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $bootstrapRequest = new ConversationBootstrapRequest(
            tenant: $tenant,
            clientKey: $clientKey,
            locale: trim((string) ($payload['locale'] ?? '')),
            conversationId: isset($payload['conversation_id']) ? trim((string) $payload['conversation_id']) : null,
            limit: max(1, min(50, (int) ($payload['limit'] ?? 20))),
        );

        $response = $this->assistantClient->bootstrapConversation($bootstrapRequest);

        return new JsonResponse([
            'ok' => true,
            'data' => [
                'conversation_id' => $response->conversationId,
                'messages' => $response->messages,
                'bundle' => [
                    'widget_url' => '/asistente-ia/widget',
                    'vector_form_url' => '/asistente-ia/vectorial',
                ],
                'raw' => $response->raw,
            ],
        ]);
    }
}
