<?php

namespace Ttpryg\AuditEngine\Contracts;

use Ttpryg\AuditEngine\Entities\AuditLog;

interface AuditRepositoryInterface
{
    public function save(AuditLog $log): AuditLog;
    public function findById(int|string $id): ?AuditLog;
    public function findByEntity(string $entityType, string|int $entityId, int $limit = 50, int $offset = 0): array;
    public function findByActor(int|string $actorId, int $limit = 50, int $offset = 0): array;
    public function findByEvent(string $eventName, int $limit = 50, int $offset = 0): array;
    public function search(array $criteria = [], int $limit = 50, int $offset = 0): array;
}
