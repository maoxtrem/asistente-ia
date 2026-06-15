<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Controller;

use Maoxtrem\AsistenteIa\DTO\IndexDocument;
use Maoxtrem\AsistenteIa\Service\ExternalIndexClient;
use Symfony\Component\HttpFoundation\Request;
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

        if ($request->isMethod('POST')) {
            $data = $this->normalizeFormData($request->request->all());
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
        $tenant = $isGlobal ? 'global' : $this->tenantName;

        return [
            'id' => trim((string) ($payload['id'] ?? $defaults['id'])),
            'type' => trim((string) ($payload['type'] ?? $defaults['type'])),
            'tenant' => $tenant,
            'title' => trim((string) ($payload['title'] ?? $defaults['title'])),
            'content' => trim((string) ($payload['content'] ?? $defaults['content'])),
            'metadata_json' => trim((string) ($payload['metadata_json'] ?? $defaults['metadata_json'])),
            'is_global' => $isGlobal,
        ];
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
