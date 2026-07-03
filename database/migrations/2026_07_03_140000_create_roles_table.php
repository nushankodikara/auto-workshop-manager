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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->json('allowed_modules');
            $table->boolean('is_custom')->default(false);
            $table->timestamps();
        });

        // Seed default roles with correct arrays of allowed modules
        DB::table('roles')->insert([
            [
                'name' => 'super-manager',
                'label' => 'Super Administrator',
                'allowed_modules' => json_encode(['dashboard', 'job-cards', 'clients', 'inventory', 'payroll', 'statistics', 'finance', 'insights', 'outsourcing', 'predefined-services', 'broadcast', 'settings']),
                'is_custom' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'manager',
                'label' => 'Workshop Manager',
                'allowed_modules' => json_encode(['dashboard', 'job-cards', 'clients', 'inventory', 'billing']),
                'is_custom' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'worker',
                'label' => 'Worker (Technician)',
                'allowed_modules' => json_encode([]),
                'is_custom' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
