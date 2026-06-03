<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\DTO;

final class ChatResponse
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $conversationId = null,
        public readonly array $raw = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'conversation_id' => $this->conversationId,
            'raw' => $this->raw,
        ];
    }
}
