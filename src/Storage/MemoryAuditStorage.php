<?php

namespace Ttpryg\AuditEngine\Storage;

use Ttpryg\AuditEngine\Contracts\AuditRepositoryInterface;
use Ttpryg\AuditEngine\Entities\AuditLog;

class MemoryAuditStorage implements AuditRepositoryInterface
{
    private array $logs = [];

    private int $autoIncrement = 1;

    public function save(AuditLog $auditLog): AuditLog
    {
        if ($auditLog->getId() === null) {
            $auditLog->setId($this->autoIncrement++);
        }
        $this->logs[$auditLog->getId()] = $auditLog;

        return $auditLog;
    }

    public function findById(int|string $id): ?AuditLog
    {
        return $this->logs[$id] ?? null;
    }

    public function findByEntity(string $entityType, string|int $entityId, int $limit = 50, int $offset = 0): array
    {
        $filtered = array_filter($this->logs, fn (AuditLog $auditLog): bool => $auditLog->getEntityType() === strtolower($entityType) && (string) $auditLog->getEntityId() === (string) $entityId);

        return array_slice(array_values($filtered), $offset, $limit);
    }

    public function findByActor(int|string $actorId, int $limit = 50, int $offset = 0): array
    {
        $filtered = array_filter($this->logs, fn (AuditLog $auditLog): bool => (string) $auditLog->getActorId() === (string) $actorId);

        return array_slice(array_values($filtered), $offset, $limit);
    }

    public function findByEvent(string $eventName, int $limit = 50, int $offset = 0): array
    {
        $filtered = array_filter($this->logs, fn (AuditLog $auditLog): bool => $auditLog->getEventName() === $eventName);

        return array_slice(array_values($filtered), $offset, $limit);
    }

    public function search(array $criteria = [], int $limit = 50, int $offset = 0): array
    {
        $filtered = array_filter($this->logs, function (AuditLog $auditLog) use ($criteria): bool {
            if (isset($criteria['entity_type']) && $auditLog->getEntityType() !== strtolower($criteria['entity_type'])) {
                return false;
            }
            if (isset($criteria['event_name']) && $auditLog->getEventName() !== $criteria['event_name']) {
                return false;
            }
            if (isset($criteria['actor_id']) && (string) $auditLog->getActorId() !== (string) $criteria['actor_id']) {
                return false;
            }

            return true;
        });

        return array_slice(array_values($filtered), $offset, $limit);
    }
}
