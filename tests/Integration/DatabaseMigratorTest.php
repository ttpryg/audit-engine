<?php

declare(strict_types=1);

namespace Ttpryg\AuditEngine\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use Ttpryg\AuditEngine\Infrastructure\Database\DatabaseMigrator;

class DatabaseMigratorTest extends TestCase
{
    private const MIGRATION = '202610200001_create_audit_logs_table';

    private PDO $pdo;

    private DatabaseMigrator $databaseMigrator;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->databaseMigrator = new DatabaseMigrator($this->pdo);
        $this->databaseMigrator->ensureTrackerTable();
    }

    // POSITIVE CASE: Tracker table is created explicitly, not automatically
    public function test_tracker_table_is_not_created_automatically(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $databaseMigrator = new DatabaseMigrator($pdo);
        $this->assertFalse($this->sqliteObjectExistsOn($pdo, 'table', 'audit_engine_migrations'));

        $databaseMigrator->ensureTrackerTable();
        $this->assertTrue($this->sqliteObjectExistsOn($pdo, 'table', 'audit_engine_migrations'));
    }

    // POSITIVE CASE: Baseline migration creates the table and its indexes
    public function test_migrate_creates_audit_logs_table_and_indexes(): void
    {
        $executed = $this->databaseMigrator->migrate();

        $this->assertSame([self::MIGRATION], $executed);
        $this->assertTrue($this->sqliteObjectExists('table', 'audit_logs'));
        $this->assertTrue($this->sqliteObjectExists('index', 'idx_entity'));
        $this->assertTrue($this->sqliteObjectExists('index', 'idx_actor'));
        $this->assertTrue($this->sqliteObjectExists('index', 'idx_event'));
    }

    // POSITIVE CASE: Re-running the migrator is a no-op and the migration is tracked
    public function test_migrate_is_idempotent_and_tracks_applied_migration(): void
    {
        $this->databaseMigrator->migrate();

        $this->assertSame([], $this->databaseMigrator->migrate());
        $this->assertSame([self::MIGRATION], $this->databaseMigrator->appliedMigrations());
        $this->assertTrue($this->sqliteObjectExists('table', 'audit_engine_migrations'));
    }

    // POSITIVE CASE: Rollback drops the table and removes the tracker entry
    public function test_rollback_drops_table_and_removes_tracker_entry(): void
    {
        $this->databaseMigrator->migrate();

        $rolledBack = $this->databaseMigrator->rollback();

        $this->assertSame([self::MIGRATION], $rolledBack);
        $this->assertFalse($this->sqliteObjectExists('table', 'audit_logs'));
        $this->assertFalse($this->sqliteObjectExists('index', 'idx_entity'));
        $this->assertSame([], $this->databaseMigrator->appliedMigrations());
    }

    // POSITIVE CASE: Baseline can be recorded without executing the migration
    public function test_mark_all_as_migrated_records_without_creating_the_table(): void
    {
        $recorded = $this->databaseMigrator->markAllAsMigrated();

        $this->assertSame([self::MIGRATION], $recorded);
        $this->assertSame([self::MIGRATION], $this->databaseMigrator->appliedMigrations());
        $this->assertFalse($this->sqliteObjectExists('table', 'audit_logs'));
        $this->assertSame([], $this->databaseMigrator->migrate());
    }

    private function sqliteObjectExists(string $type, string $name): bool
    {
        return $this->sqliteObjectExistsOn($this->pdo, $type, $name);
    }

    private function sqliteObjectExistsOn(PDO $pdo, string $type, string $name): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM sqlite_master WHERE type = :type AND name = :name');
        $stmt->execute(['type' => $type, 'name' => $name]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
