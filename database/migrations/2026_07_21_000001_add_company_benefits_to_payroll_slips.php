<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PayrollCategory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('payroll_slips', 'company_benefits')) {
            Schema::table('payroll_slips', function (Blueprint $table) {
                $table->decimal('company_benefits', 10, 2)->default(0.00)->after('deductions');
            });
        }

        // Seed default benefit categories if they don't exist
        $defaultBenefits = [
            ['name' => 'Company EPF (12%)', 'type' => 'benefit', 'default_amount' => 0.00],
            ['name' => 'Company ETF (3%)', 'type' => 'benefit', 'default_amount' => 0.00],
            ['name' => 'Food & Meals Benefit', 'type' => 'benefit', 'default_amount' => 0.00],
            ['name' => 'Health & Welfare Insurance', 'type' => 'benefit', 'default_amount' => 0.00],
        ];

        foreach ($defaultBenefits as $cat) {
            PayrollCategory::firstOrCreate(
                ['name' => $cat['name']],
                ['type' => $cat['type'], 'default_amount' => $cat['default_amount']]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('payroll_slips', 'company_benefits')) {
            Schema::table('payroll_slips', function (Blueprint $table) {
                $table->dropColumn('company_benefits');
            });
        }

        PayrollCategory::whereIn('name', [
            'Company EPF (12%)',
            'Company ETF (3%)',
            'Food & Meals Benefit',
            'Health & Welfare Insurance',
        ])->delete();
    }
};
