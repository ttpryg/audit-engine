<?php

namespace Ttpryg\AuditEngine\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Ttpryg\AuditEngine\Entities\AuditLog;
use Ttpryg\AuditEngine\Storage\PdoAuditStorage;

class PdoAuditStorageTest extends TestCase
{
    private PDO $pdo;
    private PdoAuditStorage $storage;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create SQLite Memory Table
        $this->pdo->exec("
            CREATE TABLE audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_name VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id VARCHAR(100) NOT NULL,
                actor_id INT NULL,
                old_values TEXT NULL,
                new_values TEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->storage = new PdoAuditStorage($this->pdo);
    }

    // POSITIVE CASE: Save and Find Audit Log via PDO Database Storage
    public function testSaveAndRetrieveAuditLogFromPdo(): void
    {
        $log = new AuditLog(
            eventName: 'UserStatusChangedEvent',
            entityType: 'user',
            entityId: 88,
            oldValues: ['is_active' => true],
            newValues: ['is_active' => false],
            actorId: 1,
            ipAddress: '10.0.0.1'
        );

        $saved = $this->storage->save($log);
        $this->assertNotNull($saved->getId());

        $found = $this->storage->findById($saved->getId());
        $this->assertNotNull($found);
        $this->assertEquals('UserStatusChangedEvent', $found->getEventName());
        $this->assertEquals(['is_active' => true], $found->getOldValues());
        $this->assertEquals(['is_active' => false], $found->getNewValues());

        $byActor = $this->storage->findByActor(1);
        $this->assertCount(1, $byActor);

        $byEvent = $this->storage->findByEvent('UserStatusChangedEvent');
        $this->assertCount(1, $byEvent);
    }
}
