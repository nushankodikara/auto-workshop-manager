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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // asset, liability, equity, revenue, expense
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->string('reference')->nullable();
            $table->text('description');
            $table->timestamps();
        });

        Schema::create('journal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->decimal('debit', 12, 2)->default(0.00);
            $table->decimal('credit', 12, 2)->default(0.00);
            $table->string('customer_mobile')->nullable();
            $table->timestamps();
        });

        // Seed default Chart of Accounts
        $accounts = [
            ['code' => '1000', 'name' => 'Cash & Bank', 'type' => 'asset', 'description' => 'Main company bank account and cash registry'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'description' => 'Single master account for all outstanding customer bills'],
            ['code' => '1300', 'name' => 'Parts Inventory', 'type' => 'asset', 'description' => 'Value of parts stock in inventory'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'description' => 'Outstanding supplier balances and obligations'],
            ['code' => '2100', 'name' => 'Tax Payable', 'type' => 'liability', 'description' => 'Collected tax (GST/VAT) to be paid to government'],
            ['code' => '3000', 'name' => 'Share Capital', 'type' => 'equity', 'description' => 'Initial capital contributed by shareholders'],
            ['code' => '3100', 'name' => 'Retained Earnings', 'type' => 'equity', 'description' => 'Undistributed company profits and earnings'],
            ['code' => '3200', 'name' => 'Investor Capital', 'type' => 'equity', 'description' => 'Capital contributions tracked for external investors'],
            ['code' => '4000', 'name' => 'Service Revenue', 'type' => 'revenue', 'description' => 'Income generated from labor and workshop service lines'],
            ['code' => '4100', 'name' => 'Parts Revenue', 'type' => 'revenue', 'description' => 'Income generated from parts and stock retail'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'description' => 'Purchase costs of inventory parts billed to clients'],
            ['code' => '5100', 'name' => 'Salaries Expense', 'type' => 'expense', 'description' => 'Payroll and basic/overtime salary payouts'],
            ['code' => '5200', 'name' => 'Rent & Utilities', 'type' => 'expense', 'description' => 'Workshop facility rent, power, and utility costs'],
            ['code' => '5300', 'name' => 'General Expenses', 'type' => 'expense', 'description' => 'Miscellaneous office, supplies, and administration expenses'],
        ];

        foreach ($accounts as $acc) {
            DB::table('accounts')->insert(array_merge($acc, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
    }
};
