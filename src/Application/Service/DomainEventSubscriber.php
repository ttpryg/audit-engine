<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Application\Service;

use Ttpryg\AuditEngine\Application\DTO\LogAuditRequest;
use Ttpryg\AuditEngine\Domain\Entity\AuditLog;
use Ttpryg\AuditEngine\Domain\ValueObject\AuditLogContext;

class DomainEventSubscriber
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function handle(object $event, ?AuditLogContext $auditLogContext = null): ?AuditLog
    {
        $className = $event::class;
        $shortName = (new \ReflectionClass($event))->getShortName();

        // 1. Product / Catalog events (by namespace or property)
        if (str_contains($className, 'CatalogEngine') || property_exists($event, 'product')) {
            return $this->handleCatalogEngineEvent($event, $shortName, $auditLogContext);
        }

        // 2. User / Auth events (by namespace or property)
        if (str_contains($className, 'AuthUser') || property_exists($event, 'user')) {
            return $this->handleAuthUserEvent($event, $shortName, $auditLogContext);
        }

        // 3. Content events (by namespace or property)
        if (str_contains($className, 'ContentEngine') || property_exists($event, 'content') || property_exists($event, 'contentId')) {
            return $this->handleContentEngineEvent($event, $shortName, $auditLogContext);
        }

        // 4. Cart events (by namespace or property)
        if (str_contains($className, 'CartEngine') || property_exists($event, 'cart')) {
            return $this->handleCartEngineEvent($event, $shortName, $auditLogContext);
        }

        return null;
    }

    private function handleAuthUserEvent(object $event, string $eventName, ?AuditLogContext $auditLogContext): ?AuditLog
    {
        if (! property_exists($event, 'user')) {
            return null;
        }

        $user = $event->user;
        if (! is_object($user)) {
            return null;
        }

        $userId = $this->idOf($user);

        $oldValues = property_exists($event, 'previousState') ? ['is_active' => $event->previousState] : null;
        $newValues = property_exists($event, 'newState')
            ? ['is_active' => $event->newState]
            : $this->arrayOf($user);

        return $this->auditService->log(new LogAuditRequest(
            eventName: $eventName,
            entityType: 'user',
            entityId: $userId,
            oldValues: $oldValues,
            newValues: $newValues,
            context: $this->contextWithActor($auditLogContext, $userId)
        ));
    }

    private function handleContentEngineEvent(object $event, string $eventName, ?AuditLogContext $auditLogContext): ?AuditLog
    {
        if (property_exists($event, 'content')) {
            $content = $event->content;
            if (is_object($content)) {
                return $this->auditService->log(new LogAuditRequest(
                    eventName: $eventName,
                    entityType: 'content',
                    entityId: $this->idOf($content),
                    newValues: $this->arrayOf($content),
                    context: $this->contextWithActor($auditLogContext, $this->authorIdOf($content))
                ));
            }
        }

        if (property_exists($event, 'contentId')) {
            $contentId = $event->contentId;
            if (! is_int($contentId) && ! is_string($contentId)) {
                return null;
            }

            return $this->auditService->log(new LogAuditRequest(
                eventName: $eventName,
                entityType: 'content',
                entityId: $contentId,
                oldValues: ['soft_delete' => $event->softDeleted ?? true],
                context: $auditLogContext
            ));
        }

        return null;
    }

    private function handleCatalogEngineEvent(object $event, string $eventName, ?AuditLogContext $auditLogContext): ?AuditLog
    {
        if (! property_exists($event, 'product')) {
            return null;
        }

        $product = $event->product;
        if (! is_object($product)) {
            return null;
        }

        $oldValues = null;
        $newValues = $this->arrayOf($product);

        if (
            str_contains($eventName, 'PriceChanged')
            && property_exists($event, 'oldPrice')
            && property_exists($event, 'newPrice')
        ) {
            $oldValues = ['price' => $event->oldPrice];
            $newValues = ['price' => $event->newPrice];
        } elseif (
            str_contains($eventName, 'StockUpdated')
            && property_exists($event, 'previousStock')
            && property_exists($event, 'newStock')
        ) {
            $oldValues = ['stock' => $event->previousStock];
            $newValues = ['stock' => $event->newStock];
        }

        return $this->auditService->log(new LogAuditRequest(
            eventName: $eventName,
            entityType: 'product',
            entityId: $this->idOf($product),
            oldValues: $oldValues,
            newValues: $newValues,
            context: $auditLogContext
        ));
    }

    private function handleCartEngineEvent(object $event, string $eventName, ?AuditLogContext $auditLogContext): ?AuditLog
    {
        if (! property_exists($event, 'cart')) {
            return null;
        }

        $cart = $event->cart;
        if (! is_object($cart)) {
            return null;
        }

        $newValues = null;

        $item = $event->item ?? null;
        $condition = $event->condition ?? null;

        if (is_object($item) && method_exists($item, 'toArray')) {
            $newValues = $this->arrayOf($item);
        } elseif (is_object($condition) && method_exists($condition, 'toArray')) {
            $newValues = $this->arrayOf($condition);
        }

        return $this->auditService->log(new LogAuditRequest(
            eventName: $eventName,
            entityType: 'cart',
            entityId: $this->idOf($cart),
            newValues: $newValues,
            context: $this->contextWithActor($auditLogContext, $this->userIdOf($cart))
        ));
    }

    private function contextWithActor(?AuditLogContext $auditLogContext, int|string|null $defaultActorId = null): AuditLogContext
    {
        if (! $auditLogContext instanceof AuditLogContext) {
            return new AuditLogContext(actorId: $defaultActorId);
        }

        return new AuditLogContext(
            actorId: $auditLogContext->actorId ?? $defaultActorId,
            ipAddress: $auditLogContext->ipAddress,
            userAgent: $auditLogContext->userAgent
        );
    }

    private function idOf(object $entity): int|string
    {
        if (method_exists($entity, 'getId')) {
            $id = $entity->getId();
            if (is_int($id) || is_string($id)) {
                return $id;
            }
        }

        return 'unknown';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function arrayOf(object $entity): ?array
    {
        if (! method_exists($entity, 'toArray')) {
            return null;
        }

        $data = $entity->toArray();
        if (! is_array($data)) {
            return null;
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    private function authorIdOf(object $content): int|string|null
    {
        if (! method_exists($content, 'getAuthorId')) {
            return null;
        }

        $id = $content->getAuthorId();

        return is_int($id) || is_string($id) ? $id : null;
    }

    private function userIdOf(object $cart): int|string|null
    {
        if (! method_exists($cart, 'getUserId')) {
            return null;
        }

        $id = $cart->getUserId();

        return is_int($id) || is_string($id) ? $id : null;
    }
}
