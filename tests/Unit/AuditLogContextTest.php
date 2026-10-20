<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuditEngine\Domain\ValueObject\AuditLogContext;

class AuditLogContextTest extends TestCase
{
    // POSITIVE CASE: All context values are optional
    public function test_defaults_are_null(): void
    {
        $auditLogContext = new AuditLogContext;

        $this->assertNull($auditLogContext->actorId);
        $this->assertNull($auditLogContext->ipAddress);
        $this->assertNull($auditLogContext->userAgent);
    }

    // POSITIVE CASE: Context can be built from an array
    public function test_from_array_maps_supported_keys(): void
    {
        $auditLogContext = AuditLogContext::fromArray([
            'actor_id' => 42,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'ignored' => 'value',
        ]);

        $this->assertSame(42, $auditLogContext->actorId);
        $this->assertSame('127.0.0.1', $auditLogContext->ipAddress);
        $this->assertSame('PHPUnit', $auditLogContext->userAgent);
    }

    // NEGATIVE CASE: Missing keys fall back to null
    public function test_from_array_defaults_missing_keys_to_null(): void
    {
        $auditLogContext = AuditLogContext::fromArray([]);

        $this->assertNull($auditLogContext->actorId);
        $this->assertNull($auditLogContext->ipAddress);
        $this->assertNull($auditLogContext->userAgent);
    }

    // POSITIVE CASE: Serialization keeps every supported key
    public function test_to_array_serializes_all_keys(): void
    {
        $auditLogContext = new AuditLogContext(7, '10.0.0.1', 'CLI');

        $this->assertSame([
            'actor_id' => 7,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'CLI',
        ], $auditLogContext->toArray());
    }

    // POSITIVE CASE: String actor IDs are supported
    public function test_supports_string_actor_id(): void
    {
        $auditLogContext = new AuditLogContext('user-uuid');

        $this->assertSame('user-uuid', $auditLogContext->actorId);
        $this->assertSame('user-uuid', $auditLogContext->toArray()['actor_id']);
    }
}
