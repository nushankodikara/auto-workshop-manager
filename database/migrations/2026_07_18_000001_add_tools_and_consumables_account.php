<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('accounts')->insertOrIgnore([
            'code' => '5400',
            'name' => 'Tools & Consumables',
            'type' => 'expense',
            'description' => 'Expenditures on workshop tools and consumable supplies',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('accounts')->where('code', '5400')->delete();
    }
};
