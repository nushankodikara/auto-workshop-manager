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

    /**
     * Test backup is skipped based on frequency policy.
     */
    public function test_backup_skips_based_on_frequency_policy()
    {
        // Setup daily frequency
        \App\Models\Setting::updateOrCreate(['key' => 'backup_frequency'], ['value' => 'daily']);

        // Assert directory starts empty
        $this->assertEmpty(File::glob($this->testBackupDir . '/backup_*.sqlite'));

        // Run backup 1: should succeed
        $exitCode1 = Artisan::call('db:backup');
        $this->assertEquals(0, $exitCode1);
        $this->assertCount(1, File::glob($this->testBackupDir . '/backup_*.sqlite'));

        // Run backup 2 immediately: should skip (exit code still 0, but count remains 1)
        $exitCode2 = Artisan::call('db:backup');
        $this->assertEquals(0, $exitCode2);
        $this->assertCount(1, File::glob($this->testBackupDir . '/backup_*.sqlite'));
    }

    /**
     * Test old backups are pruned based on retention days.
     */
    public function test_backup_cleanup_respects_retention_days()
    {
        // Set retention to 5 days
        \App\Models\Setting::updateOrCreate(['key' => 'backup_retention_days'], ['value' => '5']);

        // 1. Create a "very old" file
        $oldFile = $this->testBackupDir . '/backup_2026-06-01_12-00-00.sqlite';
        File::put($oldFile, '');
        touch($oldFile, time() - (10 * 24 * 3600)); // 10 days old

        // 2. Create 3 relatively fresh backup files to meet the keepCount limit
        for ($i = 0; $i < 3; $i++) {
            $freshFile = $this->testBackupDir . "/backup_2026-07-26_12-0{$i}-00.sqlite";
            File::put($freshFile, '');
            touch($freshFile, time() - (1 * 24 * 3600)); // 1 day old
        }

        // We have 4 files in total
        $this->assertCount(4, File::glob($this->testBackupDir . '/backup_*.sqlite'));

        // Run backup: this will create a new backup (now 5 files) and trigger cleanup
        $exitCode = Artisan::call('db:backup');
        $this->assertEquals(0, $exitCode);

        // The old file should be deleted (since it is >5 days and we have enough fresh backups to satisfy keepCount=3)
        $this->assertFalse(File::exists($oldFile));
        
        // Total backups should be 4 now (3 fresh ones + the new one created by the command)
        $this->assertCount(4, File::glob($this->testBackupDir . '/backup_*.sqlite'));
    }
}
