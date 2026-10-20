<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Domain\Event;

use Ttpryg\AuditEngine\Domain\Entity\AuditLog;

class AuditLoggedEvent
{
    public function __construct(
        public readonly AuditLog $auditLog
    ) {}
}
