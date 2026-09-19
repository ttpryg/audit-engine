<?php

namespace Ttpryg\AuditEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ttpryg\AuditEngine\Entities\AuditLog;
use Ttpryg\AuditEngine\Storage\FileAuditStorage;

class FileAuditStorageTest extends TestCase
{
    private string $tempDir;

    private FileAuditStorage $storage;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/audit_test_'.uniqid();
        $this->storage = new FileAuditStorage($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    // POSITIVE CASE: Save and find audit log from file storage
    public function test_save_and_find_audit_log_from_file(): void
    {
        $log = new AuditLog(
            eventName: 'UserRegistered',
            entityType: 'user',
            entityId: 1,
            newValues: ['email' => 'john@example.com'],
            actorId: 1
        );

        $saved = $this->storage->save($log);
        $this->assertNotNull($saved->getId());

        $found = $this->storage->findById($saved->getId());
        $this->assertNotNull($found);
        $this->assertEquals('UserRegistered', $found->getEventName());
        $this->assertEquals('user', $found->getEntityType());

        $byEntity = $this->storage->findByEntity('user', 1);
        $this->assertCount(1, $byEntity);
    }

    // NEGATIVE CASE: Search non-existent audit log returns empty array
    public function test_search_non_existent_returns_empty_array(): void
    {
        $results = $this->storage->findByEntity('non_existent', 9999);
        $this->assertEmpty($results);
    }
}
