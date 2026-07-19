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
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('advance_date');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, deducted, cancelled
            $table->foreignId('payroll_slip_id')->nullable()->constrained('payroll_slips')->onDelete('set null');
            $table->timestamps();
        });

        // Seed 1220 account if not exists
        if (!\App\Models\Account::where('code', '1220')->exists()) {
            \App\Models\Account::create([
                'code' => '1220',
                'name' => 'Salary Advances & Emergency Loans',
                'type' => 'asset'
            ]);
        }

        // Seed "Food / Meals Deduction" category if not exists
        if (!\App\Models\PayrollCategory::where('name', 'Food / Meals Deduction')->exists()) {
            \App\Models\PayrollCategory::create([
                'name' => 'Food / Meals Deduction',
                'type' => 'deduction',
                'default_amount' => null
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_advances');
    }
};
