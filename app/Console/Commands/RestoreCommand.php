<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore {filename : The backup file name inside backups directory or absolute path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the SQLite database from a backup file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filename = $this->argument('filename');
        $this->info("Initializing database restore from: {$filename}");

        // 1. Resolve backup file path
        $backupDir = config('database.backup_dir', env('BACKUP_DIR', base_path('backups')));
        $backupPath = $filename;

        if (!File::exists($backupPath)) {
            // Check if it exists inside the configured backups directory
            $backupPath = $backupDir . '/' . basename($filename);
        }

        if (!File::exists($backupPath)) {
            $this->error("Backup file not found at: {$filename} or {$backupPath}");
            return Command::FAILURE;
        }

        // 2. Get active database path
        $dbPath = config('database.connections.sqlite.database');
        
        // 3. Confirm action (if interactive)
        if ($this->confirm('Are you sure you want to restore? This will overwrite your active database.', true)) {
            
            // Create a temporary safety rollback backup of active db if it exists
            $tempRollback = $dbPath . '.tmp_rollback';
            $hasBackup = false;

            if (File::exists($dbPath)) {
                try {
                    // Checkpoint WAL transactions into the main sqlite file before rolling back
                    $sourceDb = new \SQLite3($dbPath);
                    $sourceDb->exec('PRAGMA wal_checkpoint(TRUNCATE);');
                    $sourceDb->close();

                    File::copy($dbPath, $tempRollback);
                    $hasBackup = true;
                } catch (\Exception $e) {
                    $this->warn("Could not create rollback backup: " . $e->getMessage() . ". Proceeding with caution.");
                }
            }

            try {
                // Delete active database -wal and -shm files if they exist to prevent WAL conflict/corruption
                if (File::exists($dbPath . '-wal')) {
                    File::delete($dbPath . '-wal');
                }
                if (File::exists($dbPath . '-shm')) {
                    File::delete($dbPath . '-shm');
                }

                // Copy backup over active database
                File::copy($backupPath, $dbPath);
                
                // Ensure proper file permissions so the web server can read/write it
                chmod($dbPath, 0777);
                
                $this->info("Database restore complete. Active database overwritten with backup.");

                // Clean up rollback copy
                if ($hasBackup && File::exists($tempRollback)) {
                    File::delete($tempRollback);
                }

                return Command::SUCCESS;
            } catch (\Exception $e) {
                $this->error("Restore failed: " . $e->getMessage());
                
                // Try to rollback
                if ($hasBackup && File::exists($tempRollback)) {
                    $this->info("Attempting rollback to previous active database...");
                    File::copy($tempRollback, $dbPath);
                    File::delete($tempRollback);
                    $this->info("Rollback complete.");
                }

                return Command::FAILURE;
            }
        }

        $this->warn('Restore cancelled by user.');
        return Command::SUCCESS;
    }
}
