<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\Contract;

interface IndexableDocumentInterface
{
    public function getIndexableId(): string;

    public function getIndexableType(): string;

    public function getIndexableSource(): string;

    public function getIndexableTenant(): string;

    public function getIndexableTitle(): string;

    public function getIndexableContent(): string;

    public function getIndexableMetadata(): array;
}
