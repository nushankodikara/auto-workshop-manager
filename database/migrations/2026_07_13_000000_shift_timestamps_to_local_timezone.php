<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Shifts all existing database timestamps (+5.5 hours) from UTC to Asia/Colombo.
     */
    public function up(): void
    {
        // Get all tables dynamically
        $tables = Schema::getTables();
        
        foreach ($tables as $tableDetail) {
            // Support object, array, or string table formats depending on database driver
            $table = is_object($tableDetail) 
                ? $tableDetail->name 
                : (is_array($tableDetail) ? ($tableDetail['name'] ?? null) : $tableDetail);

            if (empty($table) || $table === 'migrations') {
                continue;
            }
            
            $columns = Schema::getColumnListing($table);
            $hasCreatedAt = in_array('created_at', $columns);
            $hasUpdatedAt = in_array('updated_at', $columns);
            $hasCompletedAt = ($table === 'job_cards' && in_array('completed_at', $columns));
            
            if ($hasCreatedAt || $hasUpdatedAt || $hasCompletedAt) {
                $sql = "UPDATE \"{$table}\" SET ";
                $updates = [];
                
                if ($hasCreatedAt) {
                    $updates[] = "created_at = datetime(created_at, '+5 hours', '+30 minutes')";
                }
                if ($hasUpdatedAt) {
                    $updates[] = "updated_at = datetime(updated_at, '+5 hours', '+30 minutes')";
                }
                if ($hasCompletedAt) {
                    $updates[] = "completed_at = datetime(completed_at, '+5 hours', '+30 minutes')";
                }
                
                $sql .= implode(', ', $updates);
                
                DB::statement($sql);
            }
        }
    }

    /**
     * Reverse the migrations.
     * Shifts all database timestamps back (-5.5 hours) to UTC.
     */
    public function down(): void
    {
        $tables = Schema::getTables();
        
        foreach ($tables as $tableDetail) {
            $table = is_object($tableDetail) 
                ? $tableDetail->name 
                : (is_array($tableDetail) ? ($tableDetail['name'] ?? null) : $tableDetail);

            if (empty($table) || $table === 'migrations') {
                continue;
            }
            
            $columns = Schema::getColumnListing($table);
            $hasCreatedAt = in_array('created_at', $columns);
            $hasUpdatedAt = in_array('updated_at', $columns);
            $hasCompletedAt = ($table === 'job_cards' && in_array('completed_at', $columns));
            
            if ($hasCreatedAt || $hasUpdatedAt || $hasCompletedAt) {
                $sql = "UPDATE \"{$table}\" SET ";
                $updates = [];
                
                if ($hasCreatedAt) {
                    $updates[] = "created_at = datetime(created_at, '-5 hours', '-30 minutes')";
                }
                if ($hasUpdatedAt) {
                    $updates[] = "updated_at = datetime(updated_at, '-5 hours', '-30 minutes')";
                }
                if ($hasCompletedAt) {
                    $updates[] = "completed_at = datetime(completed_at, '-5 hours', '-30 minutes')";
                }
                
                $sql .= implode(', ', $updates);
                
                DB::statement($sql);
            }
        }
    }
};
