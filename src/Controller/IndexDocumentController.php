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
                    'id' => $data['id'],
                    'type' => $data['type'],
                    'source' => $data['source'],
                    'tenant' => $data['tenant'],
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'metadata' => $this->decodeMetadata($data['metadata_json']),
                    'operation' => $data['operation'],
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
     * @return array{ id:string, type:string, source:string, tenant:string, title:string, content:string, metadata_json:string, operation:string, is_global:bool }
     */
    private function defaultFormData(): array
    {
        return [
            'id' => '',
            'type' => 'custom_document',
            'source' => 'manual',
            'tenant' => $this->tenantName,
            'title' => '',
            'content' => '',
            'metadata_json' => "{\n  \"language\": \"en\",\n  \"topic\": \"language-switch\"\n}",
            'operation' => 'upsert',
            'is_global' => false,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{ id:string, type:string, source:string, tenant:string, title:string, content:string, metadata_json:string, operation:string, is_global:bool }
     */
    private function normalizeFormData(array $payload): array
    {
        $defaults = $this->defaultFormData();
        $isGlobal = $this->normalizeCheckbox($payload['is_global'] ?? false);
        $tenant = $isGlobal ? 'global' : $this->tenantName;

        return [
            'id' => trim((string) ($payload['id'] ?? $defaults['id'])),
            'type' => trim((string) ($payload['type'] ?? $defaults['type'])),
            'source' => trim((string) ($payload['source'] ?? $defaults['source'])),
            'tenant' => $tenant,
            'title' => trim((string) ($payload['title'] ?? $defaults['title'])),
            'content' => trim((string) ($payload['content'] ?? $defaults['content'])),
            'metadata_json' => trim((string) ($payload['metadata_json'] ?? $defaults['metadata_json'])),
            'operation' => trim((string) ($payload['operation'] ?? $defaults['operation'])),
            'is_global' => $isGlobal,
        ];
    }

    /**
     * @param array{ id:string, type:string, source:string, tenant:string, title:string, content:string, metadata_json:string, operation:string, is_global:bool } $data
     * @return list<string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if ($data['operation'] === 'delete') {
            foreach (['id', 'type', 'source', 'tenant'] as $field) {
                if ($data[$field] === '') {
                    $errors[] = sprintf('El campo %s es obligatorio para eliminar.', $field);
                }
            }

            return $errors;
        }

        foreach (['type', 'source', 'tenant', 'title', 'content'] as $field) {
            if ($data[$field] === '') {
                $errors[] = sprintf('El campo %s es obligatorio.', $field);
            }
        }

        if ($data['metadata_json'] !== '' && $this->decodeMetadata($data['metadata_json']) === null) {
            $errors[] = 'metadata_json debe ser JSON valido.';
        }

        return $errors;
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
