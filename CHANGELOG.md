# Changelog

All notable changes to `ttpryg/audit-engine` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[Unreleased]: https://github.com/ttpryg/audit-engine/compare/1.0.2...HEAD
[1.0.2]: https://github.com/ttpryg/audit-engine/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/ttpryg/audit-engine/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/ttpryg/audit-engine/releases/tag/1.0.0
