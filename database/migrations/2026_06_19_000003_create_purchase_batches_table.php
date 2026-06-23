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
        // 1. Alter inventory table
        Schema::table('inventory', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->default(0.00)->after('sku');
            $table->renameColumn('price', 'selling_price');
        });

        // 2. Create purchase_batches table
        Schema::create('purchase_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventory')->onDelete('cascade');
            $table->string('batch_code');
            $table->integer('quantity_received');
            $table->integer('quantity_remaining');
            $table->decimal('cost_price', 10, 2)->default(0.00);
            $table->decimal('selling_price', 10, 2)->default(0.00);
            $table->string('supplier')->nullable();
            $table->date('purchased_at');
            $table->timestamps();
        });

        // 3. Alter stock_movements table
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('purchase_batch_id')->nullable()->after('inventory_id')->constrained('purchase_batches')->onDelete('set null');
            $table->decimal('cost_price', 10, 2)->nullable()->after('quantity');
        });

        // 4. Alter bill_items table
        Schema::table('bill_items', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->default(0.00)->after('quantity');
        });

        // 5. Generate initial batches for existing inventory records
        $existingItems = DB::table('inventory')->get();
        foreach ($existingItems as $item) {
            $costPrice = round($item->selling_price * 0.7, 2); // default cost is 70% of selling price
            
            // Set cost price on inventory table
            DB::table('inventory')->where('id', $item->id)->update([
                'cost_price' => $costPrice
            ]);

            // Create initial batch
            DB::table('purchase_batches')->insert([
                'inventory_id' => $item->id,
                'batch_code' => 'BAT-INIT-' . str_pad($item->id, 3, '0', STR_PAD_LEFT),
                'quantity_received' => $item->quantity,
                'quantity_remaining' => $item->quantity,
                'cost_price' => $costPrice,
                'selling_price' => $item->selling_price,
                'supplier' => 'Initial Stock Setup',
                'purchased_at' => date('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_batches');

        Schema::table('inventory', function (Blueprint $table) {
            $table->renameColumn('selling_price', 'price');
            $table->dropColumn('cost_price');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn(['purchase_batch_id', 'cost_price']);
        });

        Schema::table('bill_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
