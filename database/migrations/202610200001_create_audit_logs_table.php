<?php

declare(strict_types=1);

use Ttpryg\DatabaseMigrator\Migration;

/**
 * Create the audit_logs table and its lookup indexes.
 *
 * The table definition is driver-aware so the same migration works on
 * SQLite (tests/embedded) and MySQL/MariaDB. Indexes are created with
 * separate statements because inline INDEX definitions are not portable.
 */
return new class extends Migration
{
    private const TABLE = 'audit_logs';

    /**
     * @var array<string, string>
     */
    private const INDEXES = [
        'idx_entity' => 'entity_type, entity_id',
        'idx_actor' => 'actor_id',
        'idx_event' => 'event_name',
    ];

    public function up(PDO $pdo): void
    {
        $pdo->exec($this->createTableStatement($pdo));

        foreach (self::INDEXES as $name => $columns) {
            if ($this->indexExists($pdo, $name)) {
                continue;
            }

            $pdo->exec(sprintf('CREATE INDEX %s ON %s (%s)', $name, self::TABLE, $columns));
        }
    }

    public function down(PDO $pdo): void
    {
        foreach (array_keys(self::INDEXES) as $name) {
            if (! $this->indexExists($pdo, $name)) {
                continue;
            }

            $pdo->exec($this->dropIndexStatement($pdo, $name));
        }

        $pdo->exec('DROP TABLE IF EXISTS '.self::TABLE);
    }

    private function createTableStatement(PDO $pdo): string
    {
        $idColumn = $this->isMysql($pdo)
            ? 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY'
            : 'id INTEGER PRIMARY KEY AUTOINCREMENT';

        $actorColumn = $this->isMysql($pdo)
            ? 'actor_id BIGINT UNSIGNED NULL'
            : 'actor_id INTEGER NULL';

        $jsonType = $this->isMysql($pdo) ? 'JSON' : 'TEXT';

        return sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                %s,
                event_name VARCHAR(100) NOT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id VARCHAR(100) NOT NULL,
                %s,
                old_values %s NULL,
                new_values %s NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )',
            self::TABLE,
            $idColumn,
            $actorColumn,
            $jsonType,
            $jsonType
        );
    }

    private function indexExists(PDO $pdo, string $index): bool
    {
        if ($this->isMysql($pdo)) {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = :table AND index_name = :index'
            );
            $stmt->execute(['table' => self::TABLE, 'index' => $index]);

            return (int) $stmt->fetchColumn() > 0;
        }

        if ($this->isSqlite($pdo)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type = 'index' AND name = :index");
            $stmt->execute(['index' => $index]);

            return (int) $stmt->fetchColumn() > 0;
        }

        return false;
    }

    private function dropIndexStatement(PDO $pdo, string $index): string
    {
        return $this->isMysql($pdo)
            ? sprintf('DROP INDEX %s ON %s', $index, self::TABLE)
            : sprintf('DROP INDEX %s', $index);
    }

    private function isMysql(PDO $pdo): bool
    {
        return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
    }

    private function isSqlite(PDO $pdo): bool
    {
        return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }
};
