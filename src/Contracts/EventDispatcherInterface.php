<?php

namespace Ttpryg\AuditEngine\Contracts;

interface EventDispatcherInterface
{
    public function dispatch(object $event): void;
}
