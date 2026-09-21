<?php

namespace Ttpryg\AuditEngine\Services;

use Ttpryg\AuditEngine\Contracts\AuditRepositoryInterface;
use Ttpryg\AuditEngine\Contracts\EventDispatcherInterface;
use Ttpryg\AuditEngine\Entities\AuditLog;
use Ttpryg\AuditEngine\Events\AuditLoggedEvent;
use Ttpryg\AuditEngine\ValueObjects\AuditLogContext;

class AuditService
{
    public function __construct(
        private readonly AuditRepositoryInterface $auditRepository,
        private readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {}

    public function log(
        string $eventName,
        string $entityType,
        string|int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null,
        int|string|null $actorId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): AuditLog {
        $auditLog = new AuditLog(
            eventName: $eventName,
            entityType: $entityType,
            entityId: $entityId,
            oldValues: $oldValues,
            newValues: $newValues,
            actorId: $actorId,
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );

        $savedLog = $this->auditRepository->save($auditLog);
        $this->eventDispatcher?->dispatch(new AuditLoggedEvent($savedLog));

        return $savedLog;
    }

    public function logWithContext(
        string $eventName,
        string $entityType,
        string|int $entityId,
        AuditLogContext $auditLogContext,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return $this->log(
            eventName: $eventName,
            entityType: $entityType,
            entityId: $entityId,
            oldValues: $oldValues,
            newValues: $newValues,
            actorId: $auditLogContext->actorId,
            ipAddress: $auditLogContext->ipAddress,
            userAgent: $auditLogContext->userAgent
        );
    }

    public function getHistoryForEntity(string $entityType, string|int $entityId, int $limit = 50, int $offset = 0): array
    {
        return $this->auditRepository->findByEntity($entityType, $entityId, $limit, $offset);
    }

    public function getLogsByActor(int|string $actorId, int $limit = 50, int $offset = 0): array
    {
        return $this->auditRepository->findByActor($actorId, $limit, $offset);
    }

    public function search(array $criteria = [], int $limit = 50, int $offset = 0): array
    {
        return $this->auditRepository->search($criteria, $limit, $offset);
    }
}
