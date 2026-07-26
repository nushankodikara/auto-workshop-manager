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

        // 2.5 Check Backup Frequency
        $frequency = \App\Models\Setting::get('backup_frequency', 'hourly');
        if ($this->shouldSkipBackup($backupDir, $frequency)) {
            $this->info("Backup skipped based on frequency policy ({$frequency}).");
            return Command::SUCCESS;
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
            
            // 5. Cleanup older backups
            $this->cleanupOldBackups($backupDir);

            // 6. Optionally upload to S3 if configured
            $s3Enabled = \App\Models\Setting::get('s3_enabled', '0') === '1';
            if ($s3Enabled) {
                $this->info('Uploading backup to S3 bucket...');
                $s3Settings = [
                    'bucket' => \App\Models\Setting::get('s3_bucket'),
                    'key' => \App\Models\Setting::get('s3_key'),
                    'secret' => \App\Models\Setting::get('s3_secret'),
                    'region' => \App\Models\Setting::get('s3_region', 'us-east-1'),
                    'endpoint' => \App\Models\Setting::get('s3_endpoint'),
                ];
                
                $uploaded = \App\Services\S3BackupService::uploadFile($backupPath, $s3Settings);
                if ($uploaded) {
                    $this->info('Successfully uploaded backup to S3.');
                } else {
                    $this->error('Failed to upload backup to S3.');
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to back up database: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Determine if backup should be skipped based on frequency.
     */
    protected function shouldSkipBackup(string $backupDir, string $frequency): bool
    {
        if ($frequency === 'hourly') {
            return false;
        }

        $files = File::glob($backupDir . '/backup_*.sqlite');
        if (empty($files)) {
            return false; // No backups exist yet
        }

        // Get the latest backup file's modified time
        $latestTime = 0;
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime > $latestTime) {
                $latestTime = $mtime;
            }
        }

        $elapsedSeconds = time() - $latestTime;

        if ($frequency === 'daily') {
            return $elapsedSeconds < (24 * 3600 - 60); // 24 hours (minus 1 min buffer)
        }

        if ($frequency === 'weekly') {
            return $elapsedSeconds < (7 * 24 * 3600 - 60); // 7 days (minus 1 min buffer)
        }

        return false;
    }

    /**
     * Remove backups older than the configured retention days.
     */
    protected function cleanupOldBackups(string $backupDir)
    {
        $files = File::glob($backupDir . '/backup_*.sqlite');
        $retentionDays = intval(\App\Models\Setting::get('backup_retention_days', '30'));
        $cutoffTime = time() - ($retentionDays * 24 * 3600);

        // Sort files by modified time ascending (oldest first)
        usort($files, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // We want to delete old files, but make sure we keep at least the last 3 backups
        $keepCount = 3;
        $totalFiles = count($files);

        for ($i = 0; $i < $totalFiles - $keepCount; $i++) {
            $file = $files[$i];
            if (filemtime($file) < $cutoffTime) {
                File::delete($file);
                $this->line("Deleted backup older than {$retentionDays} days: " . basename($file));
            }
        }
    }
}
