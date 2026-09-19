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
        private AuditRepositoryInterface $repository,
        private ?EventDispatcherInterface $eventDispatcher = null
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
        $log = new AuditLog(
            eventName: $eventName,
            entityType: $entityType,
            entityId: $entityId,
            oldValues: $oldValues,
            newValues: $newValues,
            actorId: $actorId,
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );

        $savedLog = $this->repository->save($log);
        $this->eventDispatcher?->dispatch(new AuditLoggedEvent($savedLog));

        return $savedLog;
    }

    public function logWithContext(
        string $eventName,
        string $entityType,
        string|int $entityId,
        AuditLogContext $context,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return $this->log(
            eventName: $eventName,
            entityType: $entityType,
            entityId: $entityId,
            oldValues: $oldValues,
            newValues: $newValues,
            actorId: $context->actorId,
            ipAddress: $context->ipAddress,
            userAgent: $context->userAgent
        );
    }

    public function getHistoryForEntity(string $entityType, string|int $entityId, int $limit = 50, int $offset = 0): array
    {
        return $this->repository->findByEntity($entityType, $entityId, $limit, $offset);
    }

    public function getLogsByActor(int|string $actorId, int $limit = 50, int $offset = 0): array
    {
        return $this->repository->findByActor($actorId, $limit, $offset);
    }

    public function search(array $criteria = [], int $limit = 50, int $offset = 0): array
    {
        return $this->repository->search($criteria, $limit, $offset);
    }
}
