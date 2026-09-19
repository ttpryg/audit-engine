<?php

namespace Ttpryg\AuditEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuditEngine\Entities\AuditLog;

class AuditEntityTest extends TestCase
{
    // POSITIVE CASE: Create AuditLog entity and check getters & array serialization
    public function test_audit_log_creation_and_getters(): void
    {
        $log = new AuditLog(
            eventName: 'ProductPriceChanged',
            entityType: 'product',
            entityId: 10,
            oldValues: ['price' => 100000.0],
            newValues: ['price' => 120000.0],
            actorId: 42,
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit'
        );

        $this->assertEquals('ProductPriceChanged', $log->getEventName());
        $this->assertEquals('product', $log->getEntityType());
        $this->assertEquals(10, $log->getEntityId());
        $this->assertEquals(['price' => 100000.0], $log->getOldValues());
        $this->assertEquals(['price' => 120000.0], $log->getNewValues());
        $this->assertEquals(42, $log->getActorId());
        $this->assertEquals('127.0.0.1', $log->getIpAddress());
        $this->assertEquals('PHPUnit', $log->getUserAgent());

        $array = $log->toArray();
        $this->assertEquals('ProductPriceChanged', $array['event_name']);
        $this->assertEquals('product', $array['entity_type']);
        $this->assertEquals(10, $array['entity_id']);
    }

    // POSITIVE CASE: Entity Type normalization to lowercase
    public function test_entity_type_normalized_to_lowercase(): void
    {
        $log = new AuditLog('UserRegistered', 'USER', 5);
        $this->assertEquals('user', $log->getEntityType());
    }
}
