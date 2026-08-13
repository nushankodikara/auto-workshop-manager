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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'attendance_allowance')) {
                $table->decimal('attendance_allowance', 10, 2)->default(0.00)->after('basic_salary');
            }
            if (!Schema::hasColumn('users', 'performance_allowance')) {
                $table->decimal('performance_allowance', 10, 2)->default(0.00)->after('attendance_allowance');
            }
        });

        Schema::table('payroll_slips', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_slips', 'base_attendance_allowance')) {
                $table->decimal('base_attendance_allowance', 10, 2)->default(0.00)->after('basic_salary');
            }
            if (!Schema::hasColumn('payroll_slips', 'base_performance_allowance')) {
                $table->decimal('base_performance_allowance', 10, 2)->default(0.00)->after('base_attendance_allowance');
            }
            if (!Schema::hasColumn('payroll_slips', 'attendance_allowance')) {
                $table->decimal('attendance_allowance', 10, 2)->default(0.00)->after('base_performance_allowance');
            }
            if (!Schema::hasColumn('payroll_slips', 'performance_allowance')) {
                $table->decimal('performance_allowance', 10, 2)->default(0.00)->after('attendance_allowance');
            }
            if (!Schema::hasColumn('payroll_slips', 'pay_overtime')) {
                $table->boolean('pay_overtime')->default(true)->after('performance_allowance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['attendance_allowance', 'performance_allowance']);
        });

        Schema::table('payroll_slips', function (Blueprint $table) {
            $table->dropColumn([
                'base_attendance_allowance',
                'base_performance_allowance',
                'attendance_allowance',
                'performance_allowance',
                'pay_overtime'
            ]);
        });
    }
};
