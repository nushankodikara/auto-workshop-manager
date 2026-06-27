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
        // 1. Create outsourcing_companies table
        Schema::create('outsourcing_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // 2. Create predefined_services table
        Schema::create('predefined_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('cost_price', 10, 2)->default(0.00);
            $table->decimal('selling_price', 10, 2)->default(0.00);
            $table->timestamps();
        });

        // 3. Alter bill_items table
        Schema::table('bill_items', function (Blueprint $table) {
            $table->foreignId('outsourcing_company_id')
                ->nullable()
                ->after('inventory_id')
                ->constrained('outsourcing_companies')
                ->onDelete('set null');
        });

        // 4. Alter bills table
        Schema::table('bills', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0.00)->after('tax');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });

        Schema::table('bill_items', function (Blueprint $table) {
            $table->dropForeign(['outsourcing_company_id']);
            $table->dropColumn('outsourcing_company_id');
        });

        Schema::dropIfExists('predefined_services');
        Schema::dropIfExists('outsourcing_companies');
    }
};
