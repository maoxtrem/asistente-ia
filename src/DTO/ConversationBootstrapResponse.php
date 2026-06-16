<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\DTO;

final class ConversationBootstrapResponse
{
    /**
     * @param array<int, array<string, mixed>> $messages
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly ?string $conversationId,
        public readonly array $messages = [],
        public readonly array $raw = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'messages' => $this->messages,
            'raw' => $this->raw,
        ];
    }
}
