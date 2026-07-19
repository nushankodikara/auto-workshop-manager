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
        Schema::create('job_card_transportations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('type')->default('provided'); // provided, hire
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_card_transportations');
    }
};
