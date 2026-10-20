<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Ttpryg\AuditEngine\Domain\Entity\AuditLog;
use Ttpryg\AuditEngine\Infrastructure\Database\DatabaseMigrator;
use Ttpryg\AuditEngine\Infrastructure\Persistence\Pdo\PdoAuditStorage;

class PdoAuditStorageTest extends TestCase
{
    private PDO $pdo;

    private PdoAuditStorage $pdoAuditStorage;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $databaseMigrator = new DatabaseMigrator($this->pdo);
        $databaseMigrator->ensureTrackerTable();
        $databaseMigrator->migrate();

        $this->pdoAuditStorage = new PdoAuditStorage($this->pdo);
    }

    // POSITIVE CASE: Save and Find Audit Log via PDO Database Storage
    public function test_save_and_retrieve_audit_log_from_pdo(): void
    {
        $auditLog = new AuditLog(
            eventName: 'UserStatusChangedEvent',
            entityType: 'user',
            entityId: 88,
            oldValues: ['is_active' => true],
            newValues: ['is_active' => false],
            actorId: 1,
            ipAddress: '10.0.0.1'
        );

        $saved = $this->pdoAuditStorage->save($auditLog);
        $this->assertNotNull($saved->getId());

        $found = $this->pdoAuditStorage->findById($saved->getId());
        $this->assertNotNull($found);
        $this->assertEquals('UserStatusChangedEvent', $found->getEventName());
        $this->assertEquals(['is_active' => true], $found->getOldValues());
        $this->assertEquals(['is_active' => false], $found->getNewValues());

        $byActor = $this->pdoAuditStorage->findByActor(1);
        $this->assertCount(1, $byActor);

        $byEvent = $this->pdoAuditStorage->findByEvent('UserStatusChangedEvent');
        $this->assertCount(1, $byEvent);
    }
}
