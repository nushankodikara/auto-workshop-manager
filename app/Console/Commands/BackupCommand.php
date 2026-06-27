<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a timestamped backup copy of the SQLite database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database backup...');

        // 1. Get database path
        $dbPath = config('database.connections.sqlite.database');

        if (!File::exists($dbPath)) {
            $this->error("Active SQLite database file not found at: {$dbPath}");
            return Command::FAILURE;
        }

        // 2. Get backup directory
        $backupDir = config('database.backup_dir', env('BACKUP_DIR', base_path('backups')));

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0777, true, true);
        }

        // 3. Create backup filename
        $timestamp = date('Y-m-d_H-i-s');
        $backupFilename = "backup_{$timestamp}.sqlite";
        $backupPath = $backupDir . '/' . $backupFilename;

        // 4. Perform SQLite backup
        try {
            $source = new \SQLite3($dbPath);
            $destination = new \SQLite3($backupPath);
            $source->backup($destination);
            $source->close();
            $destination->close();

            $this->info("Backup successfully created: {$backupFilename}");
            $this->info("Saved to: {$backupPath}");
            
            // 5. Cleanup older backups (keep last 30 backups)
            $this->cleanupOldBackups($backupDir);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to back up database: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Remove backups older than 30 files to prevent disk bloating.
     */
    protected function cleanupOldBackups(string $backupDir)
    {
        $files = File::glob($backupDir . '/backup_*.sqlite');
        
        if (count($files) > 30) {
            // Sort by modified time ascending (oldest first)
            usort($files, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // Delete oldest files until we have 30 left
            $toDelete = count($files) - 30;
            for ($i = 0; $i < $toDelete; $i++) {
                File::delete($files[$i]);
                $this->line("Deleted old backup: " . basename($files[$i]));
            }
        }
    }
}
