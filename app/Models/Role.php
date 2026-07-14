<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name',
        'label',
        'allowed_modules',
        'is_custom'
    ];

    protected $casts = [
        'allowed_modules' => 'array',
        'is_custom' => 'boolean'
    ];

    /**
     * Map of all available modules/features in the system and their descriptions.
     */
    public static $modules = [
        'dashboard' => 'Dashboard Overview',
        'job-cards' => 'Job Cards Board',
        'clients' => 'Clients & Vehicles',
        'inventory' => 'Inventory & Stock',
        'payroll' => 'Payroll & HR',
        'statistics' => 'Statistics & Finance',
        'finance' => 'Bookkeeping & Ledger',
        'insights' => 'Data Insights & SQL Console',
        'outsourcing' => 'Outsourcing Partners',
        'predefined-services' => 'Predefined Services',
        'broadcast' => 'Outreach Broadcasts',
        'quotations' => 'Quotation Workspace',
        'settings' => 'System Settings & Backups',
        'telemetry' => 'Tracker Telemetry'
    ];
}
