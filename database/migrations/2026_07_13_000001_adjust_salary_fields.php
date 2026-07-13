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
        // 1. Rename existing basic_salary to total_salary on users table
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('basic_salary', 'total_salary');
        });

        // 2. Add new basic_salary to users table
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('basic_salary', 10, 2)->default(0.00)->after('email');
        });

        // 3. Add total_salary to payroll_slips table
        Schema::table('payroll_slips', function (Blueprint $table) {
            $table->decimal('total_salary', 10, 2)->default(0.00)->after('basic_salary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_slips', function (Blueprint $table) {
            $table->dropColumn('total_salary');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('basic_salary');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('total_salary', 'basic_salary');
        });
    }
};
