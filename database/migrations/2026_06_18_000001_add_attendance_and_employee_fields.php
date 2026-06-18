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
        // 1. Add indexes to clients and vehicles
        Schema::table('clients', function (Blueprint $table) {
            $table->index('phone');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->index('plate_number');
        });

        // 2. Add required_days and overtime_rate columns to users
        Schema::table('users', function (Blueprint $table) {
            $table->integer('required_days')->default(26);
            $table->decimal('overtime_rate', 10, 2)->default(0.00);
        });

        // 3. Add metrics columns to payroll_slips
        Schema::table('payroll_slips', function (Blueprint $table) {
            $table->integer('required_days')->default(26);
            $table->integer('attended_days')->default(26);
            $table->decimal('overtime_hours', 10, 2)->default(0.00);
            $table->decimal('overtime_rate', 10, 2)->default(0.00);
            $table->decimal('overtime_amount', 10, 2)->default(0.00);
            $table->decimal('prorated_salary', 10, 2)->default(0.00);
        });

        // 4. Create attendances table
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('status')->default('present'); // present, absent, leave
            $table->decimal('overtime_hours', 5, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');

        Schema::table('payroll_slips', function (Blueprint $table) {
            $table->dropColumn(['required_days', 'attended_days', 'overtime_hours', 'overtime_rate', 'overtime_amount', 'prorated_salary']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['required_days', 'overtime_rate']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['plate_number']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['phone']);
        });
    }
};
