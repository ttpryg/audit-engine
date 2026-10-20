<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Domain\ValueObject;

class AuditLogContext
{
    public function __construct(
        public readonly int|string|null $actorId = null,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            actorId: $data['actor_id'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'actor_id' => $this->actorId,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}
