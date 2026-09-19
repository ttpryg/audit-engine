# AuditEngine Library

`ttpryg/audit-engine` is a framework-agnostic standalone PHP library for recording, tracking, searching, and auditing system events across your modular PHP applications (`auth-user`, `content-engine`, `catalog-engine`, `cart-engine`).

## 🌟 Key Features

- **Framework Agnostic**: Compatible with any PHP 8.1+ project (Vanilla PHP, Slim 4, Laravel, Symfony, CodeIgniter).
- **Multiple Storage Drivers**:
  - `PdoAuditStorage`: Relational database via PDO (MySQL, MariaDB, SQLite).
  - `FileAuditStorage`: Structured `.jsonl` audit log files (`storage/logs/`).
  - `MemoryAuditStorage`: In-memory storage for unit testing.
- **Domain Event Subscriber**: Includes `DomainEventSubscriber` that automatically hooks into PSR-14 domain events emitted by `auth-user`, `content-engine`, `catalog-engine`, and `cart-engine`!
- **Structured Audit Trails**: Records `event_name`, `entity_type`, `entity_id`, `actor_id` (who did it), `old_values` vs `new_values` (what changed), `ip_address`, and `user_agent`.

---

## 🗄️ Database Schema (Optional - for PDO Storage Driver)

Run the SQL script from `database/schema.sql` or use `DatabaseMigrator`:

```sql
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(100) NOT NULL,
    actor_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_actor (actor_id),
    INDEX idx_event (event_name)
);
```

---

## 🚀 Quick Usage Examples

### 1. Manual Audit Logging

```php
use PDO;
use Ttpryg\AuditEngine\Repositories\PdoAuditStorage;
use Ttpryg\AuditEngine\Services\AuditService;

$pdo = new PDO("mysql:host=localhost;dbname=my_db", "root", "secret");
$storage = new PdoAuditStorage($pdo);
$auditService = new AuditService($storage);

// Manual Audit Log
$auditService->log(
    eventName: 'ProductPriceChanged',
    entityType: 'product',
    entityId: 101,
    oldValues: ['price' => 100000],
    newValues: ['price' => 120000],
    actorId: 5,
    ipAddress: $_SERVER['REMOTE_ADDR'] ?? null
);

// Retrieve History for a Product
$history = $auditService->getHistoryForEntity('product', 101);
```

### 2. Automatic Event Subscription (Decoupled Integration)

```php
use Ttpryg\AuditEngine\Services\DomainEventSubscriber;
use Ttpryg\AuditEngine\ValueObjects\AuditLogContext;

$subscriber = new DomainEventSubscriber($auditService);
$context = new AuditLogContext(actorId: 42, ipAddress: '192.168.1.1');

// When catalog-engine emits a ProductPriceChangedEvent:
$subscriber->handle($event, $context);
```

---

## 📄 License
MIT License.
