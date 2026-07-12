<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Outsourced / Specialist Services recorded directly on the Job Card
        Schema::create('job_card_outsourcing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignId('outsourcing_company_id')
                  ->nullable()
                  ->constrained('outsourcing_companies')
                  ->nullOnDelete();
            $table->string('description');
            $table->decimal('cost_price', 10, 2)->default(0.00);
            $table->decimal('selling_price', 10, 2)->default(0.00);
            $table->timestamps();
        });

        // Misc Parts purchased directly from a dealer for a specific job (not through inventory)
        Schema::create('job_card_misc_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('cost_price', 10, 2)->default(0.00);
            $table->decimal('selling_price', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_misc_parts');
        Schema::dropIfExists('job_card_outsourcing');
    }
};
