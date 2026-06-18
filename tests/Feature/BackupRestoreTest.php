<?php

namespace Tests\Feature;

use App\Models\Shop;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    protected string $testBackupDir;
    protected string $testDbPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Setup temporary backup directory for tests
        $this->testBackupDir = base_path('tests/temp_backups');
        if (!File::exists($this->testBackupDir)) {
            File::makeDirectory($this->testBackupDir, 0777, true);
        }
        
        // 2. Setup temporary SQLite database file for testing
        $this->testDbPath = base_path('tests/temp_db.sqlite');
        if (File::exists($this->testDbPath)) {
            File::delete($this->testDbPath);
        }
        File::put($this->testDbPath, '');

        // 3. Override configurations
        config(['app.env' => 'testing']);
        config(['database.backup_dir' => $this->testBackupDir]);
        config(['database.connections.sqlite.database' => $this->testDbPath]);

        // 4. Force migrate schema onto the temporary physical database
        $this->artisan('migrate:fresh', ['--force' => true]);
    }

    protected function tearDown(): void
    {
        // Cleanup temporary backup directory
        if (File::exists($this->testBackupDir)) {
            File::deleteDirectory($this->testBackupDir);
        }

        // Cleanup temporary database files
        if (File::exists($this->testDbPath)) {
            File::delete($this->testDbPath);
        }
        
        $rollbackFile = $this->testDbPath . '.tmp_rollback';
        if (File::exists($rollbackFile)) {
            File::delete($rollbackFile);
        }

        parent::tearDown();
    }

    /**
     * Test db:backup creates backup copy.
     */
    public function test_backup_creates_sqlite_file_in_backup_directory()
    {
        // Assert directory starts empty
        $this->assertEmpty(File::glob($this->testBackupDir . '/backup_*.sqlite'));

        // Run backup
        $exitCode = Artisan::call('db:backup');
        
        $this->assertEquals(0, $exitCode);
        
        // Assert backup file is created
        $backups = File::glob($this->testBackupDir . '/backup_*.sqlite');
        $this->assertCount(1, $backups);
        $this->assertTrue(File::exists($backups[0]));
    }

    /**
     * Test db:restore overwrites active database.
     */
    public function test_restore_reverts_database_state_from_backup()
    {
        // 1. Create a shop record
        $shop = Shop::create(['name' => 'Original Shop', 'address' => '123 Main St']);

        // 2. Perform a backup
        Artisan::call('db:backup');
        $backups = File::glob($this->testBackupDir . '/backup_*.sqlite');
        $this->assertCount(1, $backups);
        $backupFile = basename($backups[0]);

        // 3. Make modifications: add another shop and delete the first
        Shop::create(['name' => 'New Shop', 'address' => '456 New Rd']);
        $shop->delete();

        // Assert DB is modified
        $this->assertDatabaseMissing('shops', ['name' => 'Original Shop']);
        $this->assertDatabaseHas('shops', ['name' => 'New Shop']);

        // 4. Run restore
        $exitCode = Artisan::call('db:restore', [
            'filename' => $backupFile,
            '--no-interaction' => true
        ]);

        $this->assertEquals(0, $exitCode);

        // 5. Assert DB state is rolled back to original backup
        $this->assertDatabaseHas('shops', ['name' => 'Original Shop']);
        $this->assertDatabaseMissing('shops', ['name' => 'New Shop']);
    }
}
