<?php

namespace Ttpryg\AuditEngine\Services;

use Ttpryg\AuditEngine\Entities\AuditLog;
use Ttpryg\AuditEngine\ValueObjects\AuditLogContext;

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
        if (property_exists($event, 'user')) {
            $user = $event->user;
            $userId = method_exists($user, 'getId') ? $user->getId() : 'unknown';

            $oldValues = property_exists($event, 'previousState') ? ['is_active' => $event->previousState] : null;
            $newValues = property_exists($event, 'newState')
                ? ['is_active' => $event->newState]
                : (method_exists($user, 'toArray') ? $user->toArray() : null);

            return $this->auditService->log(
                eventName: $eventName,
                entityType: 'user',
                entityId: $userId,
                oldValues: $oldValues,
                newValues: $newValues,
                actorId: $auditLogContext?->actorId ?? $userId,
                ipAddress: $auditLogContext?->ipAddress,
                userAgent: $auditLogContext?->userAgent
            );
        }

        return null;
    }

    private function handleContentEngineEvent(object $event, string $eventName, ?AuditLogContext $auditLogContext): ?AuditLog
    {
        if (property_exists($event, 'content')) {
            $content = $event->content;
            $contentId = method_exists($content, 'getId') ? $content->getId() : 'unknown';

            return $this->auditService->log(
                eventName: $eventName,
                entityType: 'content',
                entityId: $contentId,
                newValues: method_exists($content, 'toArray') ? $content->toArray() : null,
                actorId: $auditLogContext?->actorId ?? (method_exists($content, 'getAuthorId') ? $content->getAuthorId() : null),
                ipAddress: $auditLogContext?->ipAddress,
                userAgent: $auditLogContext?->userAgent
            );
        }

        if (property_exists($event, 'contentId')) {
            return $this->auditService->log(
                eventName: $eventName,
                entityType: 'content',
                entityId: $event->contentId,
                oldValues: ['soft_delete' => $event->softDeleted ?? true],
                actorId: $auditLogContext?->actorId,
                ipAddress: $auditLogContext?->ipAddress,
                userAgent: $auditLogContext?->userAgent
            );
        }

        return null;
    }

    private function handleCatalogEngineEvent(object $event, string $eventName, ?AuditLogContext $auditLogContext): ?AuditLog
    {
        if (property_exists($event, 'product')) {
            $product = $event->product;
            $productId = method_exists($product, 'getId') ? $product->getId() : 'unknown';

            $oldValues = null;
            $newValues = method_exists($product, 'toArray') ? $product->toArray() : null;

            if (str_contains($eventName, 'PriceChanged') && property_exists($event, 'oldPrice')) {
                $oldValues = ['price' => $event->oldPrice];
                $newValues = ['price' => $event->newPrice];
            } elseif (str_contains($eventName, 'StockUpdated') && property_exists($event, 'previousStock')) {
                $oldValues = ['stock' => $event->previousStock];
                $newValues = ['stock' => $event->newStock];
            }

            return $this->auditService->log(
                eventName: $eventName,
                entityType: 'product',
                entityId: $productId,
                oldValues: $oldValues,
                newValues: $newValues,
                actorId: $auditLogContext?->actorId,
                ipAddress: $auditLogContext?->ipAddress,
                userAgent: $auditLogContext?->userAgent
            );
        }

        return null;
    }

    private function handleCartEngineEvent(object $event, string $eventName, ?AuditLogContext $auditLogContext): ?AuditLog
    {
        if (property_exists($event, 'cart')) {
            $cart = $event->cart;
            $cartId = method_exists($cart, 'getId') ? $cart->getId() : 'unknown';
            $userId = method_exists($cart, 'getUserId') ? $cart->getUserId() : null;

            $newValues = null;
            if (property_exists($event, 'item') && method_exists($event->item, 'toArray')) {
                $newValues = $event->item->toArray();
            } elseif (property_exists($event, 'condition') && method_exists($event->condition, 'toArray')) {
                $newValues = $event->condition->toArray();
            }

            return $this->auditService->log(
                eventName: $eventName,
                entityType: 'cart',
                entityId: $cartId,
                newValues: $newValues,
                actorId: $auditLogContext?->actorId ?? $userId,
                ipAddress: $auditLogContext?->ipAddress,
                userAgent: $auditLogContext?->userAgent
            );
        }

        return null;
    }
}
