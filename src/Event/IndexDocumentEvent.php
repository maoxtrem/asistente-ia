<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Event;

use Maoxtrem\AsistenteIa\Contract\IndexableDocumentInterface;
use Maoxtrem\AsistenteIa\DTO\IndexDocument;

final class IndexDocumentEvent
{
    public function __construct(
        private readonly IndexDocument $document,
    ) {
    }

    /**
     * @param array<string, mixed> $extraMetadata
     */
    public static function fromIndexable(IndexableDocumentInterface $document, string $operation = 'upsert', array $extraMetadata = []): self
    {
        return new self(IndexDocument::fromIndexable($document, $operation, $extraMetadata));
    }

    public function document(): IndexDocument
    {
        return $this->document;
    }
}
