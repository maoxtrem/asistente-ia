<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\DTO;

final class ChatRequest
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $conversationId,
        public readonly string $tenant,
        public readonly array $context,
        public readonly array $metadata,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            message: trim((string) ($payload['message'] ?? '')),
            conversationId: self::nullableString($payload['conversation_id'] ?? null),
            tenant: trim((string) ($payload['tenant'] ?? '')),
            context: is_array($payload['context'] ?? null) ? $payload['context'] : [],
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'conversation_id' => $this->conversationId,
            'tenant' => $this->tenant,
            'context' => $this->context,
            'metadata' => $this->metadata,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }
}
