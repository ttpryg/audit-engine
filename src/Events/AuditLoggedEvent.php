<?php

namespace Ttpryg\AuditEngine\Events;

use Ttpryg\AuditEngine\Entities\AuditLog;

class AuditLoggedEvent
{
    public function __construct(
        public readonly AuditLog $auditLog
    ) {}
}
