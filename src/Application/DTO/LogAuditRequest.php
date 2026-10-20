<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Application\DTO;

use Ttpryg\AuditEngine\Domain\ValueObject\AuditLogContext;

/**
 * Input data required to record a single audit log entry.
 */
final class LogAuditRequest
{
    public function __construct(
        public readonly string $eventName,
        public readonly string $entityType,
        public readonly string|int $entityId,
        public readonly ?array $oldValues = null,
        public readonly ?array $newValues = null,
        public readonly ?AuditLogContext $context = null
    ) {}
}
