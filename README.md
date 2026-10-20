# AuditEngine Library

`ttpryg/audit-engine` is a framework-agnostic standalone PHP library for recording, tracking, searching, and auditing system events across your modular PHP applications (`auth-user`, `content-engine`, `catalog-engine`, `cart-engine`).

## 🌟 Key Features

- **Framework Agnostic**: Compatible with any PHP 8.1+ project (Vanilla PHP, Slim 4, Laravel, Symfony, CodeIgniter).
- **Multiple Storage Drivers**:
  - `PdoAuditStorage`: Relational database via PDO (MySQL, MariaDB, SQLite).
  - `FileAuditStorage`: Structured `.jsonl` audit log files (`storage/logs/`).
  - `MemoryAuditStorage`: In-memory storage for unit testing.
- **Domain Event Subscriber**: Includes `DomainEventSubscriber` that automatically hooks into PSR-14 domain events emitted by `auth-user`, `content-engine`, `catalog-engine`, and `cart-engine`!
- **PSR-14 Compatible**: Accepts any `Psr\EventDispatcher\EventDispatcherInterface` implementation (Symfony, Laravel, `ttpryg/event-dispatcher`, etc.) to dispatch `AuditLoggedEvent`. No dispatcher implementation is required by default.
- **Structured Audit Trails**: Records `event_name`, `entity_type`, `entity_id`, `actor_id` (who did it), `old_values` vs `new_values` (what changed), `ip_address`, and `user_agent`.

---

## 🗄️ Database Migrations (Optional - for PDO Storage Driver)

Schema changes are managed by `DatabaseMigrator`, backed by
[`ttpryg/database-migrator`](https://github.com/ttpryg/database-migrator).
Migrations live in `database/migrations/` and are tracked in the
`audit_engine_migrations` table.

```php
use PDO;
use Ttpryg\AuditEngine\Infrastructure\Database\DatabaseMigrator;

$pdo = new PDO('sqlite:'.__DIR__.'/audit.sqlite');

$migrator = new DatabaseMigrator($pdo);
$migrator->ensureTrackerTable(); // tracker creation is explicit by default
$migrator->migrate();            // create audit_logs and its indexes
$migrator->rollback();           // revert the latest migration
$migrator->appliedMigrations();
```

Automatic tracker-table creation is disabled by default. Pass
`autoCreateTrackerTable: true` to let the runner create it, or call
`ensureTrackerTable()` during installation/deployment.

The baseline migration is driver-aware (SQLite and MySQL/MariaDB) and creates
the `audit_logs` table plus `idx_entity`, `idx_actor`, and `idx_event` indexes.

---

## 🚀 Quick Usage Examples

### 1. Manual Audit Logging

```php
use PDO;
use Ttpryg\AuditEngine\Application\DTO\LogAuditRequest;
use Ttpryg\AuditEngine\Application\Service\AuditService;
use Ttpryg\AuditEngine\Domain\ValueObject\AuditLogContext;
use Ttpryg\AuditEngine\Infrastructure\Persistence\Pdo\PdoAuditStorage;

$pdo = new PDO("mysql:host=localhost;dbname=my_db", "root", "secret");
$storage = new PdoAuditStorage($pdo);
$auditService = new AuditService($storage);

// Manual Audit Log
$auditService->log(new LogAuditRequest(
    eventName: 'ProductPriceChanged',
    entityType: 'product',
    entityId: 101,
    oldValues: ['price' => 100000],
    newValues: ['price' => 120000],
    context: new AuditLogContext(
        actorId: 5,
        ipAddress: $_SERVER['REMOTE_ADDR'] ?? null
    )
));

// Retrieve History for a Product
$history = $auditService->getHistoryForEntity('product', 101);
```

### 2. Automatic Event Subscription (Decoupled Integration)

```php
use Ttpryg\AuditEngine\Application\Service\DomainEventSubscriber;
use Ttpryg\AuditEngine\Domain\ValueObject\AuditLogContext;

$subscriber = new DomainEventSubscriber($auditService);
$context = new AuditLogContext(actorId: 42, ipAddress: '192.168.1.1');

// When catalog-engine emits a ProductPriceChangedEvent:
$subscriber->handle($event, $context);
```

---

## 📄 License
MIT License.
