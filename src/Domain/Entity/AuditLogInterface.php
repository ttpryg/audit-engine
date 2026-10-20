<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Domain\Entity;

use DateTimeInterface;

interface AuditLogInterface
{
    public function getId(): int|string|null;

    public function getEventName(): string;

    public function getEntityType(): string;

    public function getEntityId(): string|int;

    public function getActorId(): int|string|null;

    /**
     * @return array<string, mixed>|null
     */
    public function getOldValues(): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function getNewValues(): ?array;

    public function getIpAddress(): ?string;

    public function getUserAgent(): ?string;

    public function getCreatedAt(): ?DateTimeInterface;
}
