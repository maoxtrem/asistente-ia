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
    public function __construct(private readonly ExternalAssistantClient $assistantClient)
    {
    }

    #[Route('/api/v1/asistente-ia/message', name: 'asistente_ia_message', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid JSON payload.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $chatRequest = ChatRequest::fromArray($payload);

        if ($chatRequest->message === '') {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'The message field is required.',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $response = $this->assistantClient->sendMessage($chatRequest);

        return new JsonResponse([
            'status' => 'success',
            'data' => $response->toArray(),
        ]);
    }
}
