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
        if (!Schema::hasColumn('employee_advances', 'type')) {
            Schema::table('employee_advances', function (Blueprint $table) {
                $table->string('type')->default('salary')->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('employee_advances', 'type')) {
            Schema::table('employee_advances', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
