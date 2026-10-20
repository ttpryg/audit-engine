<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuditEngine\Domain\Entity\AuditLog;
use Ttpryg\AuditEngine\Infrastructure\Persistence\InMemory\MemoryAuditStorage;

class MemoryAuditStorageTest extends TestCase
{
    private MemoryAuditStorage $memoryAuditStorage;

    protected function setUp(): void
    {
        $this->memoryAuditStorage = new MemoryAuditStorage;
    }

    // POSITIVE CASE: Saving logs without an ID assigns incremental IDs
    public function test_save_assigns_incremental_ids(): void
    {
        $auditLog = $this->memoryAuditStorage->save($this->makeLog('UserRegistered', 'user', 1, 10));
        $second = $this->memoryAuditStorage->save($this->makeLog('UserRegistered', 'user', 2, 10));

        $this->assertSame(1, $auditLog->getId());
        $this->assertSame(2, $second->getId());
    }

    // POSITIVE CASE: Saving a log that already has an ID keeps it untouched
    public function test_save_keeps_existing_id(): void
    {
        $auditLog = $this->memoryAuditStorage->save($this->makeLog('UserRegistered', 'user', 1, 10, 99));

        $this->assertSame(99, $auditLog->getId());
        $this->assertSame(1, $this->memoryAuditStorage->save($this->makeLog('UserRegistered', 'user', 2, 10))->getId());
    }

    // NEGATIVE CASE: Unknown ID returns null
    public function test_find_by_id_returns_null_for_missing_log(): void
    {
        $this->assertNull($this->memoryAuditStorage->findById(123));
    }

    // POSITIVE CASE: findByEntity filters by entity and normalizes the entity type
    public function test_find_by_entity_filters_and_normalizes_type(): void
    {
        $this->memoryAuditStorage->save($this->makeLog('UserRegistered', 'user', 1));
        $this->memoryAuditStorage->save($this->makeLog('UserRegistered', 'user', 2));

        $found = $this->memoryAuditStorage->findByEntity('USER', 1);

        $this->assertCount(1, $found);
        $this->assertSame(1, $found[0]->getEntityId());
    }

    // POSITIVE CASE: Logs can be filtered by actor
    public function test_find_by_actor_filters_logs(): void
    {
        $this->memoryAuditStorage->save($this->makeLog('UserRegistered', 'user', 1, 10));
        $this->memoryAuditStorage->save($this->makeLog('UserRegistered', 'user', 2, 20));

        $found = $this->memoryAuditStorage->findByActor(20);

        $this->assertCount(1, $found);
        $this->assertSame(2, $found[0]->getEntityId());
    }

    // POSITIVE CASE: Logs can be filtered by event name
    public function test_find_by_event_filters_logs(): void
    {
        $this->memoryAuditStorage->save($this->makeLog('UserRegistered', 'user', 1));
        $this->memoryAuditStorage->save($this->makeLog('UserDeleted', 'user', 2));

        $found = $this->memoryAuditStorage->findByEvent('UserDeleted');

        $this->assertCount(1, $found);
        $this->assertSame(2, $found[0]->getEntityId());
    }

    // POSITIVE CASE: Search combines the supported criteria
    public function test_search_combines_criteria(): void
    {
        $this->memoryAuditStorage->save($this->makeLog('UserRegistered', 'user', 1, 10));
        $this->memoryAuditStorage->save($this->makeLog('ProductCreated', 'product', 1, 10));
        $this->memoryAuditStorage->save($this->makeLog('ProductCreated', 'product', 2, 20));

        $found = $this->memoryAuditStorage->search([
            'entity_type' => 'product',
            'event_name' => 'ProductCreated',
            'actor_id' => 20,
        ]);

        $this->assertCount(1, $found);
        $this->assertSame(2, $found[0]->getEntityId());
    }

    // POSITIVE CASE: Pagination respects limit and offset
    public function test_find_by_entity_respects_limit_and_offset(): void
    {
        $this->memoryAuditStorage->save($this->makeLog('First', 'user', 1));
        $this->memoryAuditStorage->save($this->makeLog('Second', 'user', 1));
        $this->memoryAuditStorage->save($this->makeLog('Third', 'user', 1));

        $found = $this->memoryAuditStorage->findByEntity('user', 1, limit: 1, offset: 2);

        $this->assertCount(1, $found);
        $this->assertSame('Third', $found[0]->getEventName());
    }

    private function makeLog(
        string $eventName,
        string $entityType,
        int|string $entityId,
        int|string|null $actorId = null,
        int|string|null $id = null
    ): AuditLog {
        return new AuditLog(
            eventName: $eventName,
            entityType: $entityType,
            entityId: $entityId,
            actorId: $actorId,
            id: $id
        );
    }
}
