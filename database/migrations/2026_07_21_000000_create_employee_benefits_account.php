<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            if (Schema::hasTable('accounts')) {
                $exists = DB::table('accounts')->where('code', '5150')->exists();
                if (!$exists) {
                    DB::table('accounts')->insert([
                        'code' => '5150',
                        'name' => 'Employee Benefits & Welfare Expense',
                        'type' => 'expense',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if (Schema::hasTable('settings')) {
                DB::table('settings')->updateOrInsert(
                    ['key' => 'account_employee_benefits'],
                    ['value' => '5150', 'updated_at' => now()]
                );
            }
        } catch (\Throwable $e) {
            logger()->error("Migration create_employee_benefits_account notice: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            if (Schema::hasTable('accounts')) {
                DB::table('accounts')->where('code', '5150')->delete();
            }
            if (Schema::hasTable('settings')) {
                DB::table('settings')->where('key', 'account_employee_benefits')->delete();
            }
        } catch (\Throwable $e) {
            // Ignore
        }
    }
};
