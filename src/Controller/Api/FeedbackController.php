<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Controller\Api;

use Maoxtrem\AsistenteIa\DTO\FeedbackRequest;
use Maoxtrem\AsistenteIa\Service\ExternalAssistantClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class FeedbackController
{
    public function __construct(
        private readonly ExternalAssistantClient $assistantClient,
        private readonly string $tenantName,
    ) {
    }

    #[Route('/api/v1/asistente-ia/feedback', name: 'asistente_ia_feedback', methods: ['POST'])]
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
        $feedbackRequest = FeedbackRequest::fromArray($payload);

        if ($feedbackRequest->question === '' || $feedbackRequest->answer === '') {
            return new JsonResponse([
                'ok' => false,
                'error' => [
                    'message' => 'Question and answer are required.',
                    'code' => 'question_answer_required',
                ],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $response = $this->assistantClient->sendFeedback($feedbackRequest);

        return new JsonResponse([
            'ok' => $response->ok,
            'data' => $response->toArray(),
        ], $response->ok ? JsonResponse::HTTP_OK : JsonResponse::HTTP_BAD_GATEWAY);
    }
}
