<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuditEngine\Entities\AuditLog;

class AuditEntityTest extends TestCase
{
    // POSITIVE CASE: Create AuditLog entity and check getters & array serialization
    public function test_audit_log_creation_and_getters(): void
    {
        $auditLog = new AuditLog(
            eventName: 'ProductPriceChanged',
            entityType: 'product',
            entityId: 10,
            oldValues: ['price' => 100000.0],
            newValues: ['price' => 120000.0],
            actorId: 42,
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit'
        );

        $this->assertEquals('ProductPriceChanged', $auditLog->getEventName());
        $this->assertEquals('product', $auditLog->getEntityType());
        $this->assertEquals(10, $auditLog->getEntityId());
        $this->assertEquals(['price' => 100000.0], $auditLog->getOldValues());
        $this->assertEquals(['price' => 120000.0], $auditLog->getNewValues());
        $this->assertEquals(42, $auditLog->getActorId());
        $this->assertEquals('127.0.0.1', $auditLog->getIpAddress());
        $this->assertEquals('PHPUnit', $auditLog->getUserAgent());

        $array = $auditLog->toArray();
        $this->assertEquals('ProductPriceChanged', $array['event_name']);
        $this->assertEquals('product', $array['entity_type']);
        $this->assertEquals(10, $array['entity_id']);
    }

    // POSITIVE CASE: Entity Type normalization to lowercase
    public function test_entity_type_normalized_to_lowercase(): void
    {
        $auditLog = new AuditLog('UserRegistered', 'USER', 5);
        $this->assertEquals('user', $auditLog->getEntityType());
    }
}
