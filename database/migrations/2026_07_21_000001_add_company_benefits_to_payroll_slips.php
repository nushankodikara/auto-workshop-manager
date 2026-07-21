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
            if (Schema::hasTable('payroll_slips') && !Schema::hasColumn('payroll_slips', 'company_benefits')) {
                Schema::table('payroll_slips', function (Blueprint $table) {
                    $table->decimal('company_benefits', 10, 2)->default(0.00)->after('deductions');
                });
            }

            if (Schema::hasTable('payroll_categories')) {
                $defaultBenefits = [
                    ['name' => 'Company EPF (12%)', 'type' => 'benefit', 'default_amount' => 0.00],
                    ['name' => 'Company ETF (3%)', 'type' => 'benefit', 'default_amount' => 0.00],
                    ['name' => 'Food & Meals Benefit', 'type' => 'benefit', 'default_amount' => 0.00],
                    ['name' => 'Health & Welfare Insurance', 'type' => 'benefit', 'default_amount' => 0.00],
                ];

                foreach ($defaultBenefits as $cat) {
                    $exists = DB::table('payroll_categories')->where('name', $cat['name'])->exists();
                    if (!$exists) {
                        DB::table('payroll_categories')->insert([
                            'name' => $cat['name'],
                            'type' => $cat['type'],
                            'default_amount' => $cat['default_amount'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            logger()->error("Migration add_company_benefits_to_payroll_slips notice: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            if (Schema::hasTable('payroll_slips') && Schema::hasColumn('payroll_slips', 'company_benefits')) {
                Schema::table('payroll_slips', function (Blueprint $table) {
                    $table->dropColumn('company_benefits');
                });
            }

            if (Schema::hasTable('payroll_categories')) {
                DB::table('payroll_categories')->whereIn('name', [
                    'Company EPF (12%)',
                    'Company ETF (3%)',
                    'Food & Meals Benefit',
                    'Health & Welfare Insurance',
                ])->delete();
            }
        } catch (\Throwable $e) {
            // Ignore
        }
    }
};
