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
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique()->nullable();
            $table->string('unit')->default('pcs'); // cans, liters, bottles
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('consumable_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_id')->constrained('consumables')->onDelete('cascade');
            $table->string('batch_code');
            $table->decimal('quantity', 12, 2);
            $table->decimal('cost_price', 12, 2); // Total cost
            $table->string('supplier')->nullable();
            $table->date('purchased_at');
            $table->string('payment_method')->default('cash'); // cash, card, bank_transfer
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('consumable_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consumable_id')->constrained('consumables')->onDelete('cascade');
            $table->decimal('quantity_consumed', 12, 2);
            $table->date('recorded_at');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumable_usages');
        Schema::dropIfExists('consumable_purchases');
        Schema::dropIfExists('consumables');
    }
};
