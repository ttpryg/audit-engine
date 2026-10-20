# Changelog

All notable changes to `ttpryg/audit-engine` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Baseline migration `database/migrations/202610200001_create_audit_logs_table.php`
  with driver-aware `up()`/`down()` support for SQLite and MySQL/MariaDB, creating
  the `audit_logs` table and its `idx_entity`, `idx_actor`, and `idx_event` indexes.
- Migration tracking, rollback, and baseline support through
  `ttpryg/database-migrator`, recorded in the `audit_engine_migrations` table.
  Automatic tracker-table creation is disabled by default; call
  `DatabaseMigrator::ensureTrackerTable()` explicitly or pass
  `autoCreateTrackerTable: true`.

### Changed

- **Breaking:** Split the codebase into Domain and Application layers following
  Clean Architecture / DDD conventions.
  - `Entities\AuditLog` → `Domain\Entity\AuditLog`
  - `Contracts\AuditLogInterface` → `Domain\Entity\AuditLogInterface`
  - `ValueObjects\AuditLogContext` → `Domain\ValueObject\AuditLogContext`
  - `Contracts\AuditRepositoryInterface` → `Domain\Repository\AuditRepositoryInterface`
  - `Events\AuditLoggedEvent` → `Domain\Event\AuditLoggedEvent`
  - `Exceptions\*` → `Domain\Exception\*`
  - `Services\AuditService` → `Application\Service\AuditService`
  - `Services\DomainEventSubscriber` → `Application\Service\DomainEventSubscriber`
  - `Storage\PdoAuditStorage` → `Infrastructure\Persistence\Pdo\PdoAuditStorage`
  - `Storage\FileAuditStorage` → `Infrastructure\Persistence\File\FileAuditStorage`
  - `Storage\MemoryAuditStorage` → `Infrastructure\Persistence\InMemory\MemoryAuditStorage`
  - `Database\DatabaseMigrator` → `Infrastructure\Database\DatabaseMigrator`
- **Breaking:** Replaced the internal `Contracts\EventDispatcherInterface` with the
  PSR-14 `Psr\EventDispatcher\EventDispatcherInterface`, allowing any compliant
  dispatcher to be injected. `ttpryg/event-dispatcher` is now an optional
  suggestion instead of a hard dependency.
- **Breaking:** `AuditService::log()` now accepts a `LogAuditRequest` DTO instead
  of eight individual parameters. Actor, IP address, and user agent are grouped
  in an optional `AuditLogContext`.

### Removed

- Removed `database/schema.sql`; migrations in `database/migrations/` are now the
  source of truth for the schema.
- Removed the Nginx/PHP-FPM web scaffolding (`.docker/nginx` and the `web`
  service). Docker Compose now runs a single PHP CLI container for development
  tooling such as `composer test`, `composer analyse`, and `composer fix`.

## [1.1.0] - 2026-10-18

### Added

- Root `ExceptionInterface` marker interface (`Ttpryg\AuditEngine\Exceptions\ExceptionInterface`)
  that extends `Throwable` and is implemented by `AuditEngineException` (and, through
  inheritance, `AuditLogNotFoundException`), so consumers can catch all
  library-specific errors with a single type.
- `.editorconfig` for consistent editor settings across the project.

### Changed

- Added `declare(strict_types=1)` to all source files that were missing it.

## [1.0.2] - 2026-09-21

### Changed

- Enhanced the Dockerfile and CI configuration and added Rector for code-quality checks.

## [1.0.1] - 2026-09-19

### Added

- Docker configuration files for the PHP and Nginx setup.

## [1.0.0] - 2026-09-19

### Added

- Initial release: `AuditService` with pluggable storage drivers (`FileAuditStorage`,
  `MemoryAuditStorage`, `PdoAuditStorage`).
- `DomainEventSubscriber` that automatically records PSR-14 domain events emitted by
  `auth-user`, `content-engine`, `catalog-engine`, and `cart-engine`.
- Structured audit trails (`AuditLog`, `AuditLogContext`): `event_name`, `entity_type`,
  `entity_id`, `actor_id`, `old_values`/`new_values`, `ip_address`, and `user_agent`.
- Optional PDO storage schema and `DatabaseMigrator`.
