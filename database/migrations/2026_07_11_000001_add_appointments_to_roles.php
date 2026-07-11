<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'appointments' module to super-manager and manager roles in the live database.
     */
    public function up(): void
    {
        $roles = DB::table('roles')->whereIn('name', ['super-manager', 'manager'])->get();

        foreach ($roles as $role) {
            $modules = json_decode($role->allowed_modules, true) ?? [];

            if (! in_array('appointments', $modules)) {
                // Insert after 'job-cards' for logical ordering
                $pos = array_search('job-cards', $modules);
                if ($pos !== false) {
                    array_splice($modules, $pos + 1, 0, ['appointments']);
                } else {
                    $modules[] = 'appointments';
                }

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['allowed_modules' => json_encode($modules)]);
            }
        }
    }

    /**
     * Remove 'appointments' from affected roles.
     */
    public function down(): void
    {
        $roles = DB::table('roles')->whereIn('name', ['super-manager', 'manager'])->get();

        foreach ($roles as $role) {
            $modules = json_decode($role->allowed_modules, true) ?? [];
            $modules = array_values(array_filter($modules, fn($m) => $m !== 'appointments'));

            DB::table('roles')
                ->where('id', $role->id)
                ->update(['allowed_modules' => json_encode($modules)]);
        }
    }
};
