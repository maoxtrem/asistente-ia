<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\DTO;

use Maoxtrem\AsistenteIa\Contract\IndexableDocumentInterface;

final class IndexDocument
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $source,
        public readonly string $tenant,
        public readonly string $title,
        public readonly string $content,
        public readonly array $metadata = [],
        public readonly string $operation = 'upsert',
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            id: trim((string) ($payload['id'] ?? '')),
            type: trim((string) ($payload['type'] ?? '')),
            source: trim((string) ($payload['source'] ?? '')),
            tenant: trim((string) ($payload['tenant'] ?? '')),
            title: trim((string) ($payload['title'] ?? '')),
            content: trim((string) ($payload['content'] ?? '')),
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            operation: self::normalizeOperation((string) ($payload['operation'] ?? 'upsert')),
        );
    }

    public static function fromIndexable(IndexableDocumentInterface $document): self
    {
        return new self(
            id: $document->getIndexableId(),
            type: $document->getIndexableType(),
            source: $document->getIndexableSource(),
            tenant: $document->getIndexableTenant(),
            title: $document->getIndexableTitle(),
            content: $document->getIndexableContent(),
            metadata: $document->getIndexableMetadata(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'source' => $this->source,
            'tenant' => $this->tenant,
            'title' => $this->title,
            'content' => $this->content,
            'metadata' => $this->metadata,
            'operation' => $this->operation,
        ];
    }

    public function isDeletion(): bool
    {
        return $this->operation === 'delete';
    }

    private static function normalizeOperation(string $operation): string
    {
        $normalized = strtolower(trim($operation));

        return in_array($normalized, ['delete', 'upsert'], true) ? $normalized : 'upsert';
    }
}
