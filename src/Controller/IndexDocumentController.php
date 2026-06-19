<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Controller;

use Maoxtrem\AsistenteIa\DTO\IndexDocument;
use Maoxtrem\AsistenteIa\Service\ExternalIndexClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final class IndexDocumentController
{
    private const MANUAL_SOURCE = 'manual';

    public function __construct(
        private readonly Environment $twig,
        private readonly ExternalIndexClient $indexClient,
        private readonly string $tenantName,
    ) {
    }

    #[Route('/asistente-ia/vectorial', name: 'asistente_ia_vector_form', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $data = $this->defaultFormData();
        $result = null;
        $errors = [];
        $wantsJson = $this->wantsJsonResponse($request);

        if ($request->isMethod('POST')) {
            $incomingPayload = $this->readIncomingPayload($request);
            $data = $this->normalizeFormData($incomingPayload);
            $errors = $this->validate($data);

            if ($errors === []) {
                $document = IndexDocument::fromArray([
                    'id' => $data['id'] !== '' ? $data['id'] : $this->buildDocumentId($data),
                    'type' => $data['type'],
                    'source' => self::MANUAL_SOURCE,
                    'tenant' => $data['tenant'],
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'metadata' => $this->decodeMetadata($data['metadata_json']),
                    'operation' => 'upsert',
                ]);

                $result = $this->indexClient->index($document);

                if ($result->ok) {
                    $errors = [];
                } else {
                    $errors[] = $result->message;
                }
            }

            if ($wantsJson) {
                if ($errors !== []) {
                    return new JsonResponse([
                        'ok' => false,
                        'message' => implode(' ', $errors),
                        'errors' => $errors,
                        'data' => $data,
                    ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                }

                return new JsonResponse([
                    'ok' => $result?->ok ?? false,
                    'message' => $result?->message ?? 'Documento procesado.',
                    'collection' => $result?->collection,
                    'point_id' => $result?->pointId,
                    'raw' => $result?->raw ?? [],
                    'data' => $data,
                ], ($result?->ok ?? false) ? JsonResponse::HTTP_OK : JsonResponse::HTTP_BAD_GATEWAY);
            }
        }

        return new Response($this->twig->render('@AsistenteIa/index/document_form.html.twig', [
            'data' => $data,
            'result' => $result,
            'errors' => $errors,
            'tenantName' => $this->tenantName,
        ]));
    }

    /**
     * @return array{ id:string, type:string, tenant:string, title:string, content:string, metadata_json:string, is_global:bool }
     */
    private function defaultFormData(): array
    {
        return [
            'id' => '',
            'type' => 'custom_document',
            'tenant' => $this->tenantName,
            'title' => '',
            'content' => '',
            'metadata_json' => "{\n  \"language\": \"en\",\n  \"topic\": \"language-switch\"\n}",
            'is_global' => false,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ id:string, type:string, tenant:string, title:string, content:string, metadata_json:string, is_global:bool }
     */
    private function normalizeFormData(array $payload): array
    {
        $defaults = $this->defaultFormData();
        $isGlobal = $this->normalizeCheckbox($payload['is_global'] ?? false);
        $tenant = trim((string) ($payload['tenant'] ?? ''));

        if ($tenant === '') {
            $tenant = $isGlobal ? 'global' : $this->tenantName;
        } elseif ($isGlobal) {
            $tenant = 'global';
        }

        $metadataJson = trim((string) ($payload['metadata_json'] ?? ''));
        if ($metadataJson === '' && isset($payload['metadata']) && is_array($payload['metadata'])) {
            try {
                $metadataJson = json_encode($payload['metadata'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (\JsonException) {
                $metadataJson = '';
            }
        }

        return [
            'id' => trim((string) ($payload['id'] ?? $defaults['id'])),
            'type' => trim((string) ($payload['type'] ?? $defaults['type'])),
            'tenant' => $tenant,
            'title' => trim((string) ($payload['title'] ?? $defaults['title'])),
            'content' => trim((string) ($payload['content'] ?? $defaults['content'])),
            'metadata_json' => $metadataJson !== '' ? $metadataJson : $defaults['metadata_json'],
            'is_global' => $isGlobal,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readIncomingPayload(Request $request): array
    {
        $contentType = strtolower(trim((string) $request->headers->get('Content-Type', '')));

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($request->getContent(), true);

            return is_array($decoded) ? $decoded : [];
        }

        return $request->request->all();
    }

    private function wantsJsonResponse(Request $request): bool
    {
        $contentType = strtolower(trim((string) $request->headers->get('Content-Type', '')));
        $accept = strtolower(trim((string) $request->headers->get('Accept', '')));

        return str_contains($contentType, 'application/json') || str_contains($accept, 'application/json');
    }

    /**
     * @param array{ id:string, type:string, tenant:string, title:string, content:string, metadata_json:string, is_global:bool } $data
     * @return list<string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        foreach (['type', 'tenant', 'title', 'content'] as $field) {
            if ($data[$field] === '') {
                $errors[] = sprintf('El campo %s es obligatorio.', $field);
            }
        }

        if ($data['metadata_json'] !== '' && $this->decodeMetadata($data['metadata_json']) === null) {
            $errors[] = 'metadata_json debe ser JSON valido.';
        }

        return $errors;
    }

    /**
     * @param array{ id:string, type:string, tenant:string, title:string, content:string, metadata_json:string, is_global:bool } $data
     */
    private function buildDocumentId(array $data): string
    {
        $seed = implode('|', [
            $data['tenant'],
            $data['type'],
            $data['title'],
            mb_substr($data['content'], 0, 160),
        ]);

        return substr(hash('sha256', mb_strtolower($seed)), 0, 64);
    }

    private function normalizeCheckbox(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeMetadata(string $json): ?array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }
}
