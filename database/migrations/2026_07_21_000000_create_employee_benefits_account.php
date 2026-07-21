<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Account;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Account 5150 for Employee Benefits & Welfare Expense
        $benefitsAcc = Account::firstOrCreate(
            ['code' => '5150'],
            [
                'name' => 'Employee Benefits & Welfare Expense',
                'type' => 'expense'
            ]
        );

        // 2. Set default setting
        Setting::set('account_employee_benefits', '5150');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Account::where('code', '5150')->delete();
        Setting::where('key', 'account_employee_benefits')->delete();
    }
};
