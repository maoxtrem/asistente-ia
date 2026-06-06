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

    public static function fromIndexable(IndexableDocumentInterface $document): self
    {
        return new self(IndexDocument::fromIndexable($document));
    }

    public function document(): IndexDocument
    {
        return $this->document;
    }
}
