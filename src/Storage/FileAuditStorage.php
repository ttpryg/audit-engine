<?php

namespace Ttpryg\AuditEngine\Storage;

use DateTimeImmutable;
use Ttpryg\AuditEngine\Contracts\AuditRepositoryInterface;
use Ttpryg\AuditEngine\Entities\AuditLog;

class FileAuditStorage implements AuditRepositoryInterface
{
    private string $storagePath;

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = rtrim($storagePath ?? sys_get_temp_dir().'/audit_engine_logs', '/');
        if (! is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }
    }

    public function save(AuditLog $log): AuditLog
    {
        if ($log->getId() === null) {
            $log->setId(uniqid('audit_', true));
        }

        $filename = $this->storagePath.'/log_'.date('Y-m-d').'.jsonl';
        $entry = json_encode($log->toArray()).PHP_EOL;

        file_put_contents($filename, $entry, FILE_APPEND | LOCK_EX);

        return $log;
    }

    public function findById(int|string $id): ?AuditLog
    {
        $logs = $this->readAllLogs();
        foreach ($logs as $log) {
            if ((string) $log->getId() === (string) $id) {
                return $log;
            }
        }

        return null;
    }

    public function findByEntity(string $entityType, string|int $entityId, int $limit = 50, int $offset = 0): array
    {
        $logs = $this->readAllLogs();
        $filtered = array_filter($logs, function (AuditLog $log) use ($entityType, $entityId) {
            return $log->getEntityType() === strtolower($entityType) && (string) $log->getEntityId() === (string) $entityId;
        });

        return array_slice(array_values($filtered), $offset, $limit);
    }

    public function findByActor(int|string $actorId, int $limit = 50, int $offset = 0): array
    {
        $logs = $this->readAllLogs();
        $filtered = array_filter($logs, function (AuditLog $log) use ($actorId) {
            return (string) $log->getActorId() === (string) $actorId;
        });

        return array_slice(array_values($filtered), $offset, $limit);
    }

    public function findByEvent(string $eventName, int $limit = 50, int $offset = 0): array
    {
        $logs = $this->readAllLogs();
        $filtered = array_filter($logs, function (AuditLog $log) use ($eventName) {
            return $log->getEventName() === $eventName;
        });

        return array_slice(array_values($filtered), $offset, $limit);
    }

    public function search(array $criteria = [], int $limit = 50, int $offset = 0): array
    {
        $logs = $this->readAllLogs();
        $filtered = array_filter($logs, function (AuditLog $log) use ($criteria) {
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

    private function readAllLogs(): array
    {
        $files = glob($this->storagePath.'/log_*.jsonl');
        $results = [];

        foreach ($files as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                continue;
            }

            foreach ($lines as $line) {
                $data = json_decode($line, true);
                if (is_array($data)) {
                    $results[] = new AuditLog(
                        eventName: $data['event_name'],
                        entityType: $data['entity_type'],
                        entityId: $data['entity_id'],
                        oldValues: $data['old_values'] ?? null,
                        newValues: $data['new_values'] ?? null,
                        actorId: $data['actor_id'] ?? null,
                        ipAddress: $data['ip_address'] ?? null,
                        userAgent: $data['user_agent'] ?? null,
                        id: $data['id'] ?? null,
                        createdAt: ! empty($data['created_at']) ? new DateTimeImmutable($data['created_at']) : null
                    );
                }
            }
        }

        return $results;
    }
}
