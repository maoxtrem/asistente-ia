<?php

declare(strict_types=1);

namespace Maoxtrem\AsistenteIa\DTO;

final class ChatRequest
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $conversationId,
        public readonly string $clientKey,
        public readonly string $tenant,
        public readonly string $locale,
        public readonly array $context,
        public readonly array $metadata,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            message: trim((string) ($payload['message'] ?? '')),
            conversationId: self::nullableString($payload['conversation_id'] ?? null),
            clientKey: trim((string) ($payload['client_key'] ?? '')),
            tenant: trim((string) ($payload['tenant'] ?? '')),
            locale: self::normalizeLocale($payload['locale'] ?? null),
            context: is_array($payload['context'] ?? null) ? $payload['context'] : [],
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'conversation_id' => $this->conversationId,
            'client_key' => $this->clientKey,
            'tenant' => $this->tenant,
            'locale' => $this->locale,
            'context' => $this->context,
            'metadata' => $this->metadata,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private static function normalizeLocale(mixed $value): string
    {
        $normalized = strtolower(trim((string) ($value ?? '')));

        return str_replace('_', '-', $normalized);
    }
}
