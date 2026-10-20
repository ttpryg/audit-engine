<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Throwable;
use Ttpryg\AuditEngine\Domain\Exception\AuditEngineException;
use Ttpryg\AuditEngine\Domain\Exception\AuditLogNotFoundException;
use Ttpryg\AuditEngine\Domain\Exception\ExceptionInterface;

class DomainExceptionTest extends TestCase
{
    // POSITIVE CASE: The base exception is catchable through the marker interface
    public function test_base_exception_implements_marker_interface(): void
    {
        $auditEngineException = new AuditEngineException('Something failed');

        $this->assertInstanceOf(ExceptionInterface::class, $auditEngineException);
        $this->assertInstanceOf(Throwable::class, $auditEngineException);
        $this->assertSame('Something failed', $auditEngineException->getMessage());
    }

    // POSITIVE CASE: Not-found exception inherits from the base exception
    public function test_not_found_exception_extends_base_exception(): void
    {
        $auditLogNotFoundException = AuditLogNotFoundException::byId(42);

        $this->assertInstanceOf(AuditEngineException::class, $auditLogNotFoundException);
        $this->assertSame("Audit log with ID '42' was not found.", $auditLogNotFoundException->getMessage());
    }

    // POSITIVE CASE: String IDs are included in the message
    public function test_not_found_exception_supports_string_ids(): void
    {
        $auditLogNotFoundException = AuditLogNotFoundException::byId('abc-123');

        $this->assertSame("Audit log with ID 'abc-123' was not found.", $auditLogNotFoundException->getMessage());
    }

    // POSITIVE CASE: Consumers can catch every library exception via the interface
    public function test_marker_interface_catches_library_exceptions(): void
    {
        $caught = null;

        try {
            throw AuditLogNotFoundException::byId(1);
        } catch (ExceptionInterface $exception) {
            $caught = $exception;
        }

        $this->assertInstanceOf(AuditLogNotFoundException::class, $caught);
    }
}
