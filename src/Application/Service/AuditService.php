<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Application\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Ttpryg\AuditEngine\Application\DTO\LogAuditRequest;
use Ttpryg\AuditEngine\Domain\Entity\AuditLog;
use Ttpryg\AuditEngine\Domain\Event\AuditLoggedEvent;
use Ttpryg\AuditEngine\Domain\Repository\AuditRepositoryInterface;
use Ttpryg\AuditEngine\Domain\ValueObject\AuditLogContext;

class AuditService
{
    public function __construct(
        private readonly AuditRepositoryInterface $auditRepository,
        private readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {}

    public function log(LogAuditRequest $logAuditRequest): AuditLog
    {
        $context = $logAuditRequest->context ?? new AuditLogContext;

        $auditLog = new AuditLog(
            eventName: $logAuditRequest->eventName,
            entityType: $logAuditRequest->entityType,
            entityId: $logAuditRequest->entityId,
            oldValues: $logAuditRequest->oldValues,
            newValues: $logAuditRequest->newValues,
            actorId: $context->actorId,
            ipAddress: $context->ipAddress,
            userAgent: $context->userAgent
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
        return $this->log(new LogAuditRequest(
            eventName: $eventName,
            entityType: $entityType,
            entityId: $entityId,
            oldValues: $oldValues,
            newValues: $newValues,
            context: $auditLogContext
        ));
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
