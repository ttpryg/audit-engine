<?php

namespace Ttpryg\AuditEngine\Storage;

use DateTimeImmutable;
use PDO;
use Ttpryg\AuditEngine\Contracts\AuditRepositoryInterface;
use Ttpryg\AuditEngine\Entities\AuditLog;

class PdoAuditStorage implements AuditRepositoryInterface
{
    private PDO $pdo;
    private string $table;

    public function __construct(PDO $pdo, string $table = 'audit_logs')
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function save(AuditLog $log): AuditLog
    {
        $sql = "INSERT INTO {$this->table} 
                (event_name, entity_type, entity_id, actor_id, old_values, new_values, ip_address, user_agent, created_at) 
                VALUES (:event_name, :entity_type, :entity_id, :actor_id, :old_values, :new_values, :ip_address, :user_agent, :created_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'event_name' => $log->getEventName(),
            'entity_type' => $log->getEntityType(),
            'entity_id' => $log->getEntityId(),
            'actor_id' => $log->getActorId(),
            'old_values' => $log->getOldValues() ? json_encode($log->getOldValues()) : null,
            'new_values' => $log->getNewValues() ? json_encode($log->getNewValues()) : null,
            'ip_address' => $log->getIpAddress(),
            'user_agent' => $log->getUserAgent(),
            'created_at' => $log->getCreatedAt()?->format('Y-m-d H:i:s'),
        ]);

        $id = $this->pdo->lastInsertId();
        $log->setId($id);

        return $log;
    }

    public function findById(int|string $id): ?AuditLog
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? $this->mapToEntity($data) : null;
    }

    public function findByEntity(string $entityType, string|int $entityId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE entity_type = :entity_type AND entity_id = :entity_id 
                ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('entity_type', strtolower($entityType));
        $stmt->bindValue('entity_id', (string) $entityId);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapToEntity($data);
        }

        return $results;
    }

    public function findByActor(int|string $actorId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE actor_id = :actor_id 
                ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('actor_id', $actorId);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapToEntity($data);
        }

        return $results;
    }

    public function findByEvent(string $eventName, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE event_name = :event_name 
                ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('event_name', $eventName);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapToEntity($data);
        }

        return $results;
    }

    public function search(array $criteria = [], int $limit = 50, int $offset = 0): array
    {
        $where = [];
        $params = [];

        if (isset($criteria['entity_type'])) {
            $where[] = "entity_type = :entity_type";
            $params['entity_type'] = strtolower($criteria['entity_type']);
        }

        if (isset($criteria['event_name'])) {
            $where[] = "event_name = :event_name";
            $params['event_name'] = $criteria['event_name'];
        }

        if (isset($criteria['actor_id'])) {
            $where[] = "actor_id = :actor_id";
            $params['actor_id'] = $criteria['actor_id'];
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT * FROM {$this->table} {$whereSql} ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $this->mapToEntity($data);
        }

        return $results;
    }

    private function mapToEntity(array $data): AuditLog
    {
        $oldValues = !empty($data['old_values']) ? json_decode($data['old_values'], true) : null;
        $newValues = !empty($data['new_values']) ? json_decode($data['new_values'], true) : null;

        return new AuditLog(
            eventName: $data['event_name'],
            entityType: $data['entity_type'],
            entityId: $data['entity_id'],
            oldValues: is_array($oldValues) ? $oldValues : null,
            newValues: is_array($newValues) ? $newValues : null,
            actorId: $data['actor_id'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
            id: $data['id'],
            createdAt: !empty($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null
        );
    }
}
