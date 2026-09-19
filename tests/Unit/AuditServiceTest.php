<?php

namespace Ttpryg\AuditEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuditEngine\Contracts\EventDispatcherInterface;
use Ttpryg\AuditEngine\Events\AuditLoggedEvent;
use Ttpryg\AuditEngine\Services\AuditService;
use Ttpryg\AuditEngine\Services\DomainEventSubscriber;
use Ttpryg\AuditEngine\Storage\MemoryAuditStorage;

// Dummy Domain Event Classes mimicking other libraries
class DummyUserRegisteredEvent
{
    public function __construct(public readonly object $user) {}
}

class DummyProductPriceChangedEvent
{
    public function __construct(
        public readonly object $product,
        public readonly float $oldPrice,
        public readonly float $newPrice
    ) {}
}

class AuditServiceTest extends TestCase
{
    private MemoryAuditStorage $storage;

    private AuditService $service;

    protected function setUp(): void
    {
        $this->storage = new MemoryAuditStorage;
        $this->service = new AuditService($this->storage);
    }

    // POSITIVE CASE: Manual Audit Logging and Dispatching Event
    public function test_manual_audit_logging(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(AuditLoggedEvent::class));

        $service = new AuditService($this->storage, $dispatcher);
        $log = $service->log(
            eventName: 'UserLoggedIn',
            entityType: 'user',
            entityId: 42,
            actorId: 42,
            ipAddress: '192.168.1.1'
        );

        $this->assertEquals('UserLoggedIn', $log->getEventName());
        $this->assertEquals(42, $log->getActorId());

        $history = $service->getHistoryForEntity('user', 42);
        $this->assertCount(1, $history);
    }

    // POSITIVE CASE: Domain Event Subscriber Mapping
    public function test_domain_event_subscriber_maps_events(): void
    {
        $subscriber = new DomainEventSubscriber($this->service);

        // Dummy User Object
        $dummyUser = new class
        {
            public function getId()
            {
                return 100;
            }

            public function toArray()
            {
                return ['email' => 'test@example.com'];
            }
        };

        $event1 = new DummyUserRegisteredEvent($dummyUser);
        $log1 = $subscriber->handle($event1);

        $this->assertNotNull($log1);
        $this->assertEquals('DummyUserRegisteredEvent', $log1->getEventName());
        $this->assertEquals('user', $log1->getEntityType());
        $this->assertEquals(100, $log1->getEntityId());

        // Dummy Product Object
        $dummyProduct = new class
        {
            public function getId()
            {
                return 500;
            }

            public function toArray()
            {
                return ['title' => 'Shoes', 'price' => 150000];
            }
        };

        $event2 = new DummyProductPriceChangedEvent($dummyProduct, 100000.0, 150000.0);
        $log2 = $subscriber->handle($event2);

        $this->assertNotNull($log2);
        $this->assertEquals(['price' => 100000.0], $log2->getOldValues());
        $this->assertEquals(['price' => 150000.0], $log2->getNewValues());
    }
}
