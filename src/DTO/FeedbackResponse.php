<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\DTO;

final class FeedbackResponse
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly array $raw = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'message' => $this->message,
            'raw' => $this->raw,
        ];
    }
}
