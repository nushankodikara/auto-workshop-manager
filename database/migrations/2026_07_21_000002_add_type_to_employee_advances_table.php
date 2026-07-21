<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            if (Schema::hasTable('employee_advances') && !Schema::hasColumn('employee_advances', 'type')) {
                Schema::table('employee_advances', function (Blueprint $table) {
                    $table->string('type')->default('salary')->after('user_id');
                });
            }
        } catch (\Throwable $e) {
            logger()->error("Migration add_type_to_employee_advances_table notice: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            if (Schema::hasTable('employee_advances') && Schema::hasColumn('employee_advances', 'type')) {
                Schema::table('employee_advances', function (Blueprint $table) {
                    $table->dropColumn('type');
                });
            }
        } catch (\Throwable $e) {
            // Ignore
        }
    }
};
