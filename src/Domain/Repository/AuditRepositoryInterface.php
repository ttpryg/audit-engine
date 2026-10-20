<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Domain\Repository;

use Ttpryg\AuditEngine\Domain\Entity\AuditLog;

interface AuditRepositoryInterface
{
    public function save(AuditLog $auditLog): AuditLog;

    public function findById(int|string $id): ?AuditLog;

    /**
     * @return array<int, AuditLog>
     */
    public function findByEntity(string $entityType, string|int $entityId, int $limit = 50, int $offset = 0): array;

    /**
     * @return array<int, AuditLog>
     */
    public function findByActor(int|string $actorId, int $limit = 50, int $offset = 0): array;

    /**
     * @return array<int, AuditLog>
     */
    public function findByEvent(string $eventName, int $limit = 50, int $offset = 0): array;

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<int, AuditLog>
     */
    public function search(array $criteria = [], int $limit = 50, int $offset = 0): array;
}
