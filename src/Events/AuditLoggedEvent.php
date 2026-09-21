<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Events;

use Ttpryg\AuditEngine\Entities\AuditLog;

class AuditLoggedEvent
{
    public function __construct(
        public readonly AuditLog $auditLog
    ) {}
}
