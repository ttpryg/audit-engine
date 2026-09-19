<?php

namespace Ttpryg\AuditEngine\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use Ttpryg\AuditEngine\Contracts\AuditLogInterface;

class AuditLog implements AuditLogInterface
{
    private int|string|null $id;

    private string $eventName;

    private string $entityType;

    private string|int $entityId;

    private int|string|null $actorId;

    private ?array $oldValues;

    private ?array $newValues;

    private ?string $ipAddress;

    private ?string $userAgent;

    private ?DateTimeInterface $createdAt;

    public function __construct(
        string $eventName,
        string $entityType,
        string|int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        int|string|null $actorId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        int|string|null $id = null,
        ?DateTimeInterface $createdAt = null
    ) {
        $this->id = $id;
        $this->eventName = $eventName;
        $this->entityType = strtolower($entityType);
        $this->entityId = $entityId;
        $this->oldValues = $oldValues;
        $this->newValues = $newValues;
        $this->actorId = $actorId;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
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
