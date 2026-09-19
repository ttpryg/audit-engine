<?php

namespace Ttpryg\AuditEngine\Storage;

use Ttpryg\AuditEngine\Contracts\AuditRepositoryInterface;
use Ttpryg\AuditEngine\Entities\AuditLog;

class MemoryAuditStorage implements AuditRepositoryInterface
{
    private array $logs = [];
    private int $autoIncrement = 1;

    public function save(AuditLog $log): AuditLog
    {
        if ($log->getId() === null) {
            $log->setId($this->autoIncrement++);
        }
        $this->logs[$log->getId()] = $log;
        return $log;
    }

    public function findById(int|string $id): ?AuditLog
    {
        return $this->logs[$id] ?? null;
    }

    public function findByEntity(string $entityType, string|int $entityId, int $limit = 50, int $offset = 0): array
    {
        $filtered = array_filter($this->logs, function (AuditLog $log) use ($entityType, $entityId) {
            return $log->getEntityType() === strtolower($entityType) && (string) $log->getEntityId() === (string) $entityId;
        });

        return array_slice(array_values($filtered), $offset, $limit);
    }

    public function findByActor(int|string $actorId, int $limit = 50, int $offset = 0): array
    {
        $filtered = array_filter($this->logs, function (AuditLog $log) use ($actorId) {
            return (string) $log->getActorId() === (string) $actorId;
        });

        return array_slice(array_values($filtered), $offset, $limit);
    }

    public function findByEvent(string $eventName, int $limit = 50, int $offset = 0): array
    {
        $filtered = array_filter($this->logs, function (AuditLog $log) use ($eventName) {
            return $log->getEventName() === $eventName;
        });

        return array_slice(array_values($filtered), $offset, $limit);
    }

    public function search(array $criteria = [], int $limit = 50, int $offset = 0): array
    {
        $filtered = array_filter($this->logs, function (AuditLog $log) use ($criteria) {
            if (isset($criteria['entity_type']) && $log->getEntityType() !== strtolower($criteria['entity_type'])) {
                return false;
            }
            if (isset($criteria['event_name']) && $log->getEventName() !== $criteria['event_name']) {
                return false;
            }
            if (isset($criteria['actor_id']) && (string) $log->getActorId() !== (string) $criteria['actor_id']) {
                return false;
            }
            return true;
        });

        return array_slice(array_values($filtered), $offset, $limit);
    }
}
