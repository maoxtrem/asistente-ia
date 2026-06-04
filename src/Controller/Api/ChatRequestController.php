<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Controller\Api;

use Maoxtrem\AsistenteIa\DTO\ChatRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ChatRequestController
{
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

        $conversationId = $chatRequest->conversationId ?? 'mock-' . substr(hash('sha256', $chatRequest->message . '|' . (string) $request->getClientIp()), 0, 12);
        $reply = sprintf(
            'He recibido tu mensaje: "%s". En este momento estoy en modo de prueba y respondo como si el microservicio estuviera conectado.',
            $chatRequest->message
        );

        return new JsonResponse([
            'ok' => true,
            'data' => [
                'message' => $reply,
                'conversation_id' => $conversationId,
                'raw' => [
                    'mocked' => true,
                    'source' => 'asistente-ia-bundle',
                ],
            ],
        ]);
    }
}
