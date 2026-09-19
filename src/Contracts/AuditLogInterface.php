<?php

namespace Ttpryg\AuditEngine\Contracts;

use DateTimeInterface;

interface AuditLogInterface
{
    public function getId(): int|string|null;

    public function getEventName(): string;

    public function getEntityType(): string;

    public function getEntityId(): string|int;

    public function getActorId(): int|string|null;

    public function getOldValues(): ?array;

    public function getNewValues(): ?array;

    public function getIpAddress(): ?string;

    public function getUserAgent(): ?string;

    public function getCreatedAt(): ?DateTimeInterface;
}
