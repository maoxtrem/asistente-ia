<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\DTO;

final class ConversationBootstrapRequest
{
    public function __construct(
        public readonly string $tenant,
        public readonly string $clientKey,
        public readonly string $locale,
        public readonly ?string $conversationId = null,
        public readonly int $limit = 20,
    ) {
    }

    public function toArray(): array
    {
        return [
            'tenant' => $this->tenant,
            'client_key' => $this->clientKey,
            'locale' => $this->locale,
            'conversation_id' => $this->conversationId,
            'limit' => $this->limit,
        ];
    }
}
