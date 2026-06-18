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
        // 1. Shops (Locations)
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        // 2. Clients
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        // 3. Vehicles
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('make');
            $table->string('model');
            $table->integer('year');
            $table->string('plate_number');
            $table->string('vin')->nullable();
            $table->timestamps();
        });

        // 4. Job Cards
        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('received-vehicle'); // received-vehicle, on-going, blocked, testing, waiting-to-pickup
            $table->text('notes')->nullable();
            $table->decimal('estimated_cost', 10, 2)->default(0.00);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 5. Job Card Worker Pivot (Multi-worker assignment)
        Schema::create('job_card_worker', function (Blueprint $table) {
            $table->foreignId('job_card_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->primary(['job_card_id', 'user_id']);
        });

        // 6. Job Card Comments
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->timestamps();
        });

        // 7. Activity History on Job Cards
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // e.g. status_changed, worker_assigned, inventory_added
            $table->text('details')->nullable();
            $table->timestamps();
        });

        // 8. Inventory (Stock Management)
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->integer('quantity')->default(0);
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('unit')->default('pcs'); // pcs, liters, kg, etc.
            $table->timestamps();
        });

        // 9. Stock Movements
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventory')->onDelete('cascade');
            $table->foreignId('job_card_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // in, out, adjustment
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 10. Bills / Invoices
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained()->onDelete('cascade');
            $table->string('bill_number')->unique();
            $table->decimal('tax', 10, 2)->nullable(); // numeric optional
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('status')->default('draft'); // draft, paid
            $table->timestamps();
        });

        // 11. Bill Line Items
        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_id')->nullable()->constrained('inventory')->onDelete('set null');
            $table->string('type'); // part, labor
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('total_price', 10, 2)->default(0.00);
            $table->timestamps();
        });

        // 12. Payroll Categories
        Schema::create('payroll_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // addition, deduction
            $table->decimal('default_amount', 10, 2)->nullable();
            $table->timestamps();
        });

        // 13. Payroll Slips
        Schema::create('payroll_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('allowance', 10, 2)->default(0.00);
            $table->decimal('deductions', 10, 2)->default(0.00);
            $table->decimal('net_salary', 10, 2);
            $table->string('status')->default('draft'); // draft, paid
            $table->timestamps();
        });

        // 14. Payroll Slip Custom Additions/Deductions
        Schema::create('payroll_slip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_slip_id')->constrained()->onDelete('cascade');
            $table->string('category_name');
            $table->string('type'); // addition, deduction
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_slip_items');
        Schema::dropIfExists('payroll_slips');
        Schema::dropIfExists('payroll_categories');
        Schema::dropIfExists('bill_items');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('job_card_worker');
        Schema::dropIfExists('job_cards');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('shops');
    }
};
