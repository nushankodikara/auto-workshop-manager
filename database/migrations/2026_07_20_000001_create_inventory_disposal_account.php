<?php

use Illuminate\Database\Migrations\Migration;
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
        // 1. Seed Account 5600: Inventory Shrinkage & Disposal Expense
        $account = Account::firstOrCreate(
            ['code' => '5600'],
            [
                'name' => 'Inventory Shrinkage & Disposal Expense',
                'type' => 'expense',
                'description' => 'Losses and write-offs from damaged, expired, stolen, or disposed inventory items'
            ]
        );

        // 2. Register system setting for account_inventory_disposal
        Setting::updateOrCreate(
            ['key' => 'account_inventory_disposal'],
            ['value' => '5600']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Account::where('code', '5600')->delete();
        Setting::where('key', 'account_inventory_disposal')->delete();
    }
};
