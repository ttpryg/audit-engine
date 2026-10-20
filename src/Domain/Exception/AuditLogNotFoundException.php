<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Domain\Exception;

class AuditLogNotFoundException extends AuditEngineException
{
    public static function byId(int|string $id): self
    {
        return new self("Audit log with ID '{$id}' was not found.");
    }
}
