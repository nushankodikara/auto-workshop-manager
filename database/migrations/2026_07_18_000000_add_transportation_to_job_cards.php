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
        // 1. Add transportation_fee and transportation_type columns to job_cards
        if (!Schema::hasColumn('job_cards', 'transportation_fee')) {
            Schema::table('job_cards', function (Blueprint $table) {
                $table->decimal('transportation_fee', 10, 2)->default(0.00)->after('estimated_cost');
            });
        }

        if (!Schema::hasColumn('job_cards', 'transportation_type')) {
            Schema::table('job_cards', function (Blueprint $table) {
                $table->string('transportation_type')->default('provided')->after('transportation_fee'); // provided, hire
            });
        }

        // 2. Seed default Transportation accounts in Chart of Accounts
        $accounts = [
            [
                'code' => '1030',
                'name' => 'Transportation Account',
                'type' => 'asset',
                'description' => 'Cash and bank ledger where transportation and towing collections accumulate',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => '4200',
                'name' => 'Transportation Revenue',
                'type' => 'revenue',
                'description' => 'Income generated from client transportation, towing, and pickup/delivery fees',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => '5500',
                'name' => 'Transportation Hire Expense',
                'type' => 'expense',
                'description' => 'Expense ledger tracking payments to hired third-party towing providers',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($accounts as $acc) {
            DB::table('accounts')->insertOrIgnore($acc);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn(['transportation_fee', 'transportation_type']);
        });

        DB::table('accounts')->whereIn('code', ['1030', '4200', '5500'])->delete();
    }
};
