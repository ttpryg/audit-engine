<?php

namespace Ttpryg\AuditEngine\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use Ttpryg\AuditEngine\Contracts\AuditLogInterface;

class AuditLog implements AuditLogInterface
{
    private readonly string $entityType;

    private readonly ?DateTimeInterface $createdAt;

    public function __construct(
        private readonly string $eventName,
        string $entityType,
        private readonly string|int $entityId,
        private readonly ?array $oldValues = null,
        private readonly ?array $newValues = null,
        private readonly int|string|null $actorId = null,
        private readonly ?string $ipAddress = null,
        private readonly ?string $userAgent = null,
        private int|string|null $id = null,
        ?DateTimeInterface $createdAt = null
    ) {
        $this->entityType = strtolower($entityType);
        $this->createdAt = $createdAt ?? new DateTimeImmutable;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setId(int|string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): string|int
    {
        return $this->entityId;
    }

    public function getActorId(): int|string|null
    {
        return $this->actorId;
    }

    public function getOldValues(): ?array
    {
        return $this->oldValues;
    }

    public function getNewValues(): ?array
    {
        return $this->newValues;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'event_name' => $this->eventName,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'actor_id' => $this->actorId,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'created_at' => $this->createdAt?->format(DateTimeInterface::ATOM),
        ];
    }
}
